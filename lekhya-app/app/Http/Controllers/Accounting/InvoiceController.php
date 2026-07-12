<?php
namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use App\Models\{Invoice, Party, PartyBranch, FiscalYear, Account, HsnSacCode, AiSuggestion};
use App\Services\Accounting\InvoicePostingService;
use App\Services\GST\GstRateEngine;
use App\Services\AI\InvoiceExtractionValidator;
use Illuminate\Http\Request;

class InvoiceController extends Controller {
    public function __construct(private InvoicePostingService $posting, private GstRateEngine $rateEngine) {}

    public function index(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $type = $request->get('type', 'sales');
        $invoices = Invoice::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->with('party')
            ->latest('invoice_date')
            ->paginate(20);
        return view('accounting.invoices.index', compact('invoices', 'type'));
    }

    public function create(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $type = $request->get('type', 'sales');
        $tenant = auth()->user()->tenant;
        $parties = Party::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $accounts = Account::where('tenant_id', $tenantId)->where('is_ledger', true)->get();
        $hsnCodes = HsnSacCode::limit(200)->get();
        $fiscalYear = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->first();

        // Pre-fill from an approved AI invoice extraction (?ai_suggestion=id).
        $prefill = $this->prefillFromExtraction($request, $tenantId, $parties);

        return view('accounting.invoices.form', compact('parties', 'accounts', 'hsnCodes', 'type', 'fiscalYear', 'tenant', 'prefill'));
    }

    private function prefillFromExtraction(Request $request, int $tenantId, $parties): ?array {
        if (! $request->filled('ai_suggestion')) {
            return null;
        }
        $suggestion = AiSuggestion::where('tenant_id', $tenantId)
            ->where('type', 'extraction')
            ->find($request->get('ai_suggestion'));
        if (! $suggestion) {
            return null;
        }

        $ex = $suggestion->suggestion ?? [];

        // Match the extracted party to an existing one — by GSTIN first, then name.
        $gstin = strtoupper((string) ($ex['party_gstin'] ?? ''));
        $name  = trim((string) ($ex['party_name'] ?? ''));
        $match = null;
        if ($gstin) {
            $match = $parties->first(fn($p) => strtoupper((string) $p->gstin) === $gstin);
        }
        if (! $match && $name) {
            $match = $parties->first(fn($p) => strcasecmp(trim((string) $p->name), $name) === 0);
        }

        $lines = collect($ex['lines'] ?? [])->map(fn($l) => [
            'description'      => $l['description'] ?? '',
            'hsn_sac_code'     => (string) ($l['hsn_sac'] ?? $l['hsn_sac_code'] ?? ''),
            'quantity'         => $l['quantity'] ?? 1,
            'unit'             => $l['unit'] ?? 'nos',
            'rate'             => $l['rate'] ?? ($l['amount'] ?? ''),
            'discount_percent' => $l['discount_percent'] ?? 0,
            'gst_rate'         => $l['gst_rate'] ?? null, // preview fallback when HSN isn't in the rate table
        ])->values()->all();

        // The duplicate-resolution step can pin an explicit party (and branch),
        // overriding the name/GSTIN match above.
        if ($request->filled('party_id')) {
            $match = $parties->firstWhere('id', (int) $request->get('party_id')) ?: $match;
        }
        $branch = $request->filled('party_branch_id')
            ? \App\Models\PartyBranch::where('tenant_id', $tenantId)->find((int) $request->get('party_branch_id'))
            : null;

        $validation = app(InvoiceExtractionValidator::class)->validate($ex);

        return [
            'suggestion_id'    => $suggestion->id,
            'party_id'         => $match?->id,
            'party_name'       => $match?->name ?: $name,
            'party_gstin'      => $gstin ?: null,
            'party_matched'    => (bool) $match,
            'party_branch_id'  => $branch?->id,
            'branch_label'     => $branch?->label,
            'branch_gstin'     => $branch?->gstin,
            'reference_number' => $ex['invoice_number'] ?? null, // the vendor's own bill number
            'invoice_date'     => $ex['invoice_date'] ?? null,
            'due_date'         => $ex['due_date'] ?? null,
            'notes'            => $ex['payment_terms'] ?? null,
            'lines'            => $lines ?: null,
            'validation'       => $validation,
        ];
    }

    public function store(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate([
            'type' => 'required|in:sales,purchase',
            'party_id' => 'required|exists:parties,id',
            'party_branch_id' => 'nullable|integer|exists:party_branches,id',
            'reference_number' => 'nullable|string|max:100',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.hsn_sac_code' => 'nullable|string',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.unit' => 'nullable|string|max:20',
            'lines.*.rate' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'lines.*.gst_rate' => 'nullable|numeric|min:0|max:100',
        ]);
        $tenant = auth()->user()->tenant;
        $party = Party::findOrFail($validated['party_id']);
        $fiscalYear = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->firstOrFail();
        $lines = $request->input('lines', []);

        // A bill can be booked against a branch (a different GST registration of
        // the same vendor) — its state, not the parent's, drives the GST split.
        $branch = ! empty($validated['party_branch_id'])
            ? PartyBranch::where('tenant_id', $tenantId)->where('party_id', $party->id)->find($validated['party_branch_id'])
            : null;
        $validated['party_branch_id'] = $branch?->id; // ignore a mismatched branch

        $supplierState = $tenant->state_code ?? '';
        $buyerState = ($branch?->state_code ?: $party->state_code) ?: $supplierState;
        $isInterstate = $supplierState !== $buyerState;

        $subtotal = 0; $taxableTotal = 0; $cgstTotal = 0; $sgstTotal = 0; $igstTotal = 0;
        $computedLines = [];
        foreach ($lines as $line) {
            $gross = $line['quantity'] * $line['rate'];
            $taxable = round($gross * (1 - ($line['discount_percent'] ?? 0) / 100), 4);

            // Honour the bill's own GST rate when it was captured (e.g. a scanned
            // vendor bill), so the recorded tax matches the bill exactly. Fall
            // back to the HSN rate engine for manually-entered lines.
            $billRate = isset($line['gst_rate']) && is_numeric($line['gst_rate']) ? (float) $line['gst_rate'] : 0.0;
            if ($billRate > 0) {
                $rates = [
                    'cgst_rate' => $isInterstate ? 0 : $billRate / 2,
                    'sgst_rate' => $isInterstate ? 0 : $billRate / 2,
                    'igst_rate' => $isInterstate ? $billRate : 0,
                ];
                $tax = [
                    'cgst_amount' => round($taxable * $rates['cgst_rate'] / 100, 2),
                    'sgst_amount' => round($taxable * $rates['sgst_rate'] / 100, 2),
                    'igst_amount' => round($taxable * $rates['igst_rate'] / 100, 2),
                ];
                $tax['total_tax'] = $tax['cgst_amount'] + $tax['sgst_amount'] + $tax['igst_amount'];
            } else {
                $rates = $this->rateEngine->getRates($line['hsn_sac_code'] ?? '', $supplierState, $buyerState);
                $tax = $this->rateEngine->calculateTax($taxable, $rates);
            }

            $subtotal += $gross;
            $taxableTotal += $taxable;
            $cgstTotal += $tax['cgst_amount'];
            $sgstTotal += $tax['sgst_amount'];
            $igstTotal += $tax['igst_amount'];

            $computedLines[] = array_merge($line, [
                'taxable_amount' => $taxable,
                'cgst_rate' => $rates['cgst_rate'], 'cgst_amount' => $tax['cgst_amount'],
                'sgst_rate' => $rates['sgst_rate'], 'sgst_amount' => $tax['sgst_amount'],
                'igst_rate' => $rates['igst_rate'], 'igst_amount' => $tax['igst_amount'],
                'line_total' => round($taxable + $tax['total_tax'], 4),
            ]);
        }
        $totalTax = $cgstTotal + $sgstTotal + $igstTotal;
        $totalAmount = round($taxableTotal + $totalTax, 2);

        $invoice = Invoice::create(array_merge($validated, [
            'tenant_id' => $tenantId,
            'fiscal_year_id' => $fiscalYear->id,
            'invoice_number' => $this->nextNumber($tenantId, $validated['type']),
            'status' => 'draft',
            'place_of_supply' => $buyerState,
            'is_interstate' => $isInterstate,
            'subtotal' => $subtotal,
            'taxable_amount' => $taxableTotal,
            'cgst_amount' => $cgstTotal,
            'sgst_amount' => $sgstTotal,
            'igst_amount' => $igstTotal,
            'total_tax' => $totalTax,
            'total_amount' => $totalAmount,
            'balance_amount' => $totalAmount,
            'created_by' => auth()->id(),
        ]));

        foreach ($computedLines as $i => $line) {
            $invoice->lines()->create(array_merge($line, [
                'tenant_id' => $tenantId,
                'line_order' => $i,
            ]));
        }

        return redirect()->route('accounting.invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice) {
        $this->authorize('view', $invoice);
        return view('accounting.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice) {
        if ($invoice->isLocked()) return back()->with('error', 'Cannot edit a posted invoice.');
        $tenantId = auth()->user()->tenant_id;
        $parties = Party::where('tenant_id', $tenantId)->get();
        return view('accounting.invoices.form', compact('invoice', 'parties'));
    }

    public function update(Request $request, Invoice $invoice) {
        if ($invoice->isLocked()) return back()->with('error', 'Cannot edit a posted invoice.');
        // update logic similar to store
        return redirect()->route('accounting.invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function post(Invoice $invoice) {
        try {
            $this->posting->post($invoice, auth()->id());
            return back()->with('success', 'Invoice posted to ledger successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Invoice $invoice) {
        if ($invoice->status === 'posted') {
            return back()->with('error', 'Use a credit note to cancel a posted invoice.');
        }
        $invoice->update(['status' => 'cancelled']);
        return back()->with('success', 'Invoice cancelled.');
    }

    public function destroy(Invoice $invoice) {
        if ($invoice->isLocked()) return back()->with('error', 'Cannot delete a posted invoice.');
        $invoice->delete();
        return redirect()->route('accounting.invoices.index')->with('success', 'Invoice deleted.');
    }

    private function nextNumber(int $tenantId, string $type): string {
        $prefix = $type === 'sales' ? 'SI' : 'PI';
        $year = date('y');
        // Include soft-deleted rows and derive from the highest existing sequence —
        // count() alone reuses numbers after a delete and hits the unique constraint.
        $last = Invoice::withTrashed()
            ->where('tenant_id', $tenantId)->where('type', $type)
            ->where('invoice_number', 'like', "{$prefix}/{$year}/%")
            ->pluck('invoice_number')
            ->map(fn($n) => (int) substr((string) $n, strrpos((string) $n, '/') + 1))
            ->max() ?? 0;
        return "{$prefix}/{$year}/" . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }
}
