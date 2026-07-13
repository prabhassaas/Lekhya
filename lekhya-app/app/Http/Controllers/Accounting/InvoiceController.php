<?php
namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use App\Models\{Invoice, Party, PartyBranch, FiscalYear, Account, HsnSacCode, TenantItem, AiSuggestion};
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

        $lines = collect($ex['lines'] ?? [])->map(function ($l) use ($tenantId) {
            $desc = $l['description'] ?? '';
            $hsn  = (string) ($l['hsn_sac'] ?? $l['hsn_sac_code'] ?? '');
            $rate = $l['gst_rate'] ?? null;
            $rateMissing = ($rate === null || $rate === '');

            // Fill gaps the OCR left: first from what we've learned about this
            // product on past bills, then from the HSN master rate table.
            if ($desc !== '' && ($hsn === '' || $rateMissing) && ($item = TenantItem::match($tenantId, $desc))) {
                if ($hsn === '' && $item->hsn_sac) {
                    $hsn = $item->hsn_sac;
                }
                if ($rateMissing && $item->gst_rate !== null) {
                    $rate = (float) $item->gst_rate;
                    $rateMissing = false;
                }
            }
            if ($rateMissing && $hsn !== '' && ($hsnRate = $this->hsnRate($hsn)) !== null) {
                $rate = $hsnRate;
            }

            return [
                'description'      => $desc,
                'hsn_sac_code'     => $hsn,
                'quantity'         => $l['quantity'] ?? 1,
                'unit'             => $l['unit'] ?? 'nos',
                'rate'             => $l['rate'] ?? ($l['amount'] ?? ''),
                'discount_percent' => $l['discount_percent'] ?? 0,
                'gst_rate'         => $rate, // OCR value, else learned/HSN-master rate
            ];
        })->values()->all();

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
        $validated = $request->validate($this->rules());
        $tenant = auth()->user()->tenant;
        $party = Party::where('tenant_id', $tenantId)->findOrFail($validated['party_id']);
        $fiscalYear = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->firstOrFail();

        $c = $this->computeInvoice($validated, $request->input('lines', []), $tenant, $party);

        $invoice = Invoice::create(array_merge($validated, $c['header'], [
            'tenant_id' => $tenantId,
            'fiscal_year_id' => $fiscalYear->id,
            'invoice_number' => $this->nextNumber($tenantId, $validated['type']),
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]));

        $this->syncLines($invoice, $c['lines'], $tenantId);
        $this->learnFromLines($tenantId, $c['lines']);

        return redirect()->route('accounting.invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice) {
        $this->authorize('view', $invoice);
        return view('accounting.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice) {
        $this->authorize('view', $invoice);
        if ($invoice->isLocked()) return redirect()->route('accounting.invoices.show', $invoice)->with('error', 'A posted invoice is locked — reverse it with a credit/debit note to change it.');

        $tenantId = auth()->user()->tenant_id;
        $type = $invoice->type;
        $tenant = auth()->user()->tenant;
        $parties = Party::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $accounts = Account::where('tenant_id', $tenantId)->where('is_ledger', true)->get();
        $hsnCodes = HsnSacCode::limit(200)->get();
        $fiscalYear = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->first();
        $editing = true;

        // Reuse the create form by feeding it the invoice's own values as prefill.
        $prefill = [
            'party_id'         => $invoice->party_id,
            'party_name'       => $invoice->party->name ?? '',
            'party_gstin'      => $invoice->party->gstin ?? null,
            'party_matched'    => true,
            'party_branch_id'  => $invoice->party_branch_id,
            'branch_label'     => $invoice->branch->label ?? null,
            'branch_gstin'     => $invoice->branch->gstin ?? null,
            'reference_number' => $invoice->reference_number,
            'invoice_date'     => optional($invoice->invoice_date)->format('Y-m-d'),
            'due_date'         => optional($invoice->due_date)->format('Y-m-d'),
            'notes'            => $invoice->notes,
            'lines'            => $invoice->lines->map(fn ($l) => [
                'description'      => $l->description,
                'hsn_sac_code'     => (string) $l->hsn_sac_code,
                'quantity'         => (float) $l->quantity,
                'unit'             => $l->unit ?: 'nos',
                'rate'             => (float) $l->rate,
                'discount_percent' => (float) $l->discount_percent,
                'gst_rate'         => (float) ($l->cgst_rate + $l->sgst_rate + $l->igst_rate),
            ])->values()->all(),
            'validation'       => null,
        ];

        return view('accounting.invoices.form', compact('parties', 'accounts', 'hsnCodes', 'type', 'fiscalYear', 'tenant', 'prefill', 'editing', 'invoice'));
    }

    public function update(Request $request, Invoice $invoice) {
        $this->authorize('view', $invoice);
        if ($invoice->isLocked()) return redirect()->route('accounting.invoices.show', $invoice)->with('error', 'A posted invoice is locked and cannot be edited.');

        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate($this->rules());
        $tenant = auth()->user()->tenant;
        $party = Party::where('tenant_id', $tenantId)->findOrFail($validated['party_id']);

        // Keep the original number/type — editing a draft never re-numbers it.
        unset($validated['type']);
        $c = $this->computeInvoice($validated, $request->input('lines', []), $tenant, $party);

        $invoice->update(array_merge($validated, $c['header'], [
            'balance_amount' => $c['header']['total_amount'], // unpaid draft
        ]));
        $this->syncLines($invoice, $c['lines'], $tenantId);
        $this->learnFromLines($tenantId, $c['lines']);

        return redirect()->route('accounting.invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    /** Validation rules shared by store() and update(). */
    private function rules(): array {
        return [
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
        ];
    }

    /**
     * Compute per-line tax + header totals. Mutates $validated to pin a valid
     * branch. Returns ['lines' => computed line rows, 'header' => invoice totals].
     */
    private function computeInvoice(array &$validated, array $lines, $tenant, Party $party): array {
        $tenantId = $tenant->id;
        // A bill can be booked against a branch (a different GST registration of
        // the same vendor) — its state, not the parent's, drives the GST split.
        $branch = ! empty($validated['party_branch_id'])
            ? PartyBranch::where('tenant_id', $tenantId)->where('party_id', $party->id)->find($validated['party_branch_id'])
            : null;
        $validated['party_branch_id'] = $branch?->id; // ignore a mismatched branch

        // State codes drive IGST vs CGST/SGST. Fall back to the first two digits
        // of the GSTIN when the state field is blank.
        $supplierState = $this->stateOf($tenant->state_code, $tenant->gstin);
        $partyState    = $this->stateOf($branch?->state_code ?: $party->state_code, $branch?->gstin ?: $party->gstin);
        $buyerState    = $partyState ?: $supplierState;
        $isInterstate  = $supplierState !== '' && $buyerState !== '' && $supplierState !== $buyerState;

        $subtotal = 0; $taxableTotal = 0; $cgstTotal = 0; $sgstTotal = 0; $igstTotal = 0;
        $computedLines = [];
        foreach ($lines as $line) {
            $gross = $line['quantity'] * $line['rate'];
            $taxable = round($gross * (1 - ($line['discount_percent'] ?? 0) / 100), 4);

            // Honour the line's own GST rate when set, so the recorded tax matches
            // the bill. Fall back to the HSN rate engine otherwise.
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

        return [
            'lines' => $computedLines,
            'header' => [
                'place_of_supply' => $buyerState,
                'is_interstate'   => $isInterstate,
                'subtotal'        => $subtotal,
                'taxable_amount'  => $taxableTotal,
                'cgst_amount'     => $cgstTotal,
                'sgst_amount'     => $sgstTotal,
                'igst_amount'     => $igstTotal,
                'total_tax'       => $totalTax,
                'total_amount'    => $totalAmount,
                'balance_amount'  => $totalAmount,
            ],
        ];
    }

    /** Replace the invoice's line rows with the freshly computed set. */
    private function syncLines(Invoice $invoice, array $computedLines, int $tenantId): void {
        $invoice->lines()->delete();
        foreach ($computedLines as $i => $line) {
            $invoice->lines()->create(array_merge($line, ['tenant_id' => $tenantId, 'line_order' => $i]));
        }
    }

    /** Remember each product's HSN + effective GST rate for future scans. */
    private function learnFromLines(int $tenantId, array $computedLines): void {
        foreach ($computedLines as $line) {
            if (! empty($line['description'])) {
                $rate = (float) (($line['cgst_rate'] ?? 0) + ($line['sgst_rate'] ?? 0) + ($line['igst_rate'] ?? 0));
                TenantItem::learn($tenantId, $line['description'], $line['hsn_sac_code'] ?? null, $rate, $line['unit'] ?? null);
            }
        }
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

    /** State code from the field, or the first 2 digits of the GSTIN as a fallback. */
    private function stateOf(?string $stateCode, ?string $gstin): string {
        $s = trim((string) $stateCode);
        if ($s !== '') return $s;
        $g = trim((string) $gstin);
        return strlen($g) >= 2 ? substr($g, 0, 2) : '';
    }

    /** Total GST rate for an HSN/SAC from the master — exact code, then 4-digit chapter. */
    private function hsnRate(string $hsn): ?float {
        $hsn = trim($hsn);
        if ($hsn === '') return null;
        $row = HsnSacCode::where('code', $hsn)->first()
            ?? (strlen($hsn) > 4 ? HsnSacCode::where('code', substr($hsn, 0, 4))->first() : null);
        return $row ? (float) $row->igst_rate : null;
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
