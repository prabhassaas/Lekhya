<?php
namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use App\Models\{Invoice, Party, PartyBranch, FiscalYear, Account, HsnSacCode, TenantItem, AiSuggestion, Product};
use App\Services\Accounting\InvoicePostingService;
use App\Services\Accounting\JournalEngine;
use App\Services\GST\GstRateEngine;
use App\Services\AI\InvoiceExtractionValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller {
    public function __construct(private InvoicePostingService $posting, private GstRateEngine $rateEngine, private JournalEngine $journalEngine) {}

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
        $products = Product::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $fiscalYear = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->first();

        // Pre-fill from an approved AI invoice extraction (?ai_suggestion=id).
        $prefill = $this->prefillFromExtraction($request, $tenantId, $parties);

        return view('accounting.invoices.form', compact('parties', 'accounts', 'hsnCodes', 'products', 'type', 'fiscalYear', 'tenant', 'prefill'));
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

            $meta = (isset($l['meta']) && is_array($l['meta'])) ? array_filter($l['meta'], fn ($v) => $v !== null && $v !== '') : [];

            return [
                'description'      => $desc,
                'hsn_sac_code'     => $hsn,
                'quantity'         => $l['quantity'] ?? 1,
                'unit'             => $l['unit'] ?? 'nos',
                'rate'             => $l['rate'] ?? ($l['amount'] ?? ''),
                'discount_percent' => $l['discount_percent'] ?? 0,
                'gst_rate'         => $rate, // OCR value, else learned/HSN-master rate
                'meta'             => $meta ?: null,
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
            'gst_inclusive'    => (bool) ($ex['gst_inclusive'] ?? false),
            'tds_rate'         => (isset($ex['tds_rate']) && is_numeric($ex['tds_rate'])) ? (float) $ex['tds_rate'] : null,
            'classification'   => $ex['party_classification'] ?? null,
            'lines'            => $lines ?: null,
            'validation'       => $validation,
        ];
    }

    public function store(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate($this->rules());
        $tenant = auth()->user()->tenant;
        $party = Party::where('tenant_id', $tenantId)->findOrFail($validated['party_id']);
        $this->alignPartyType($party, $validated['type']);
        $fiscalYear = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->firstOrFail();

        // Document type only applies to sales; purchases are always bills.
        $docType = $validated['type'] === 'sales' ? ($validated['document_type'] ?? 'tax_invoice') : 'tax_invoice';
        $validated['document_type'] = $docType;

        $c = $this->computeInvoice($validated, $request->input('lines', []), $tenant, $party);

        // TDS to deduct on payment (mainly for service providers).
        $tdsRate = isset($validated['tds_rate']) && is_numeric($validated['tds_rate']) ? (float) $validated['tds_rate'] : null;
        $extra = [
            'tds_amount' => $tdsRate ? round($c['header']['taxable_amount'] * $tdsRate / 100, 2) : null,
        ] + $this->sourceFileFrom($request, $tenantId);

        $invoice = Invoice::create(array_merge($validated, $c['header'], $extra, [
            'tenant_id' => $tenantId,
            'fiscal_year_id' => $fiscalYear->id,
            'invoice_number' => $this->nextNumber($tenantId, $validated['type'], $docType),
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]));

        $this->syncLines($invoice, $c['lines'], $tenantId);
        $this->learnFromLines($tenantId, $c['lines']);
        $this->linkSuggestion($request, $tenantId, $invoice->id);

        return redirect()->route('accounting.invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice) {
        $this->authorize('view', $invoice);
        return view('accounting.invoices.show', compact('invoice'));
    }

    /** Serve the original scanned invoice file inline (tenant-scoped). */
    public function original(Invoice $invoice) {
        $this->authorize('view', $invoice);
        $path = $invoice->originalFilePath();
        abort_unless($path && \Illuminate\Support\Facades\Storage::exists($path), 404, 'No original file on record.');
        return \Illuminate\Support\Facades\Storage::response($path, $invoice->source_file_name ?: 'invoice-' . $invoice->invoice_number);
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
        $products = Product::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
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
            'gst_inclusive'    => (bool) $invoice->price_includes_gst,
            'tds_rate'         => $invoice->tds_rate !== null ? (float) $invoice->tds_rate : null,
            'lines'            => $invoice->lines->map(fn ($l) => [
                'description'      => $l->description,
                'hsn_sac_code'     => (string) $l->hsn_sac_code,
                'quantity'         => (float) $l->quantity,
                'unit'             => $l->unit ?: 'nos',
                'rate'             => (float) $l->rate,
                'discount_percent' => (float) $l->discount_percent,
                'gst_rate'         => (float) ($l->cgst_rate + $l->sgst_rate + $l->igst_rate),
                'meta'             => $l->meta ?: null,
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
        $this->alignPartyType($party, $invoice->type);

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
            'document_type' => 'nullable|in:tax_invoice,proforma,delivery_challan',
            'party_id' => 'required|exists:parties,id',
            'party_branch_id' => 'nullable|integer|exists:party_branches,id',
            'reference_number' => 'nullable|string|max:100',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'price_includes_gst' => 'nullable|boolean',
            'tds_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.hsn_sac_code' => 'nullable|string',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.unit' => 'nullable|string|max:20',
            'lines.*.rate' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'lines.*.gst_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.meta' => 'nullable|string',
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

        // When the bill prints GST-inclusive prices, the line amount already
        // contains the tax and must be grossed down to find the taxable value.
        $priceIncl = ! empty($validated['price_includes_gst']);

        $subtotal = 0; $taxableTotal = 0; $cgstTotal = 0; $sgstTotal = 0; $igstTotal = 0;
        $computedLines = [];
        foreach ($lines as $line) {
            $gross   = (float) ($line['quantity'] ?? 0) * (float) ($line['rate'] ?? 0);
            $net     = round($gross * (1 - ($line['discount_percent'] ?? 0) / 100), 4);

            // Resolve the per-head GST% — the line's own rate wins, else the HSN engine.
            $billRate = isset($line['gst_rate']) && is_numeric($line['gst_rate']) ? (float) $line['gst_rate'] : 0.0;
            if ($billRate > 0) {
                $rates = [
                    'cgst_rate' => $isInterstate ? 0 : $billRate / 2,
                    'sgst_rate' => $isInterstate ? 0 : $billRate / 2,
                    'igst_rate' => $isInterstate ? $billRate : 0,
                ];
            } else {
                $rates = $this->rateEngine->getRates($line['hsn_sac_code'] ?? '', $supplierState, $buyerState);
            }
            $effRate = (float) $rates['cgst_rate'] + (float) $rates['sgst_rate'] + (float) $rates['igst_rate'];

            // Inclusive → back out the embedded tax; exclusive → net is the taxable.
            $taxable = ($priceIncl && $effRate > 0) ? round($net / (1 + $effRate / 100), 4) : $net;

            $tax = [
                'cgst_amount' => round($taxable * $rates['cgst_rate'] / 100, 2),
                'sgst_amount' => round($taxable * $rates['sgst_rate'] / 100, 2),
                'igst_amount' => round($taxable * $rates['igst_rate'] / 100, 2),
            ];
            $tax['total_tax'] = $tax['cgst_amount'] + $tax['sgst_amount'] + $tax['igst_amount'];

            // Preserve any extra extracted attributes for this row (dimension, etc.).
            $meta = $line['meta'] ?? null;
            if (is_string($meta)) { $d = json_decode($meta, true); $meta = is_array($d) && $d ? $d : null; }
            elseif (is_array($meta)) { $meta = array_filter($meta, fn ($v) => $v !== null && $v !== ''); $meta = $meta ?: null; }

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
                'meta' => $meta,
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
        if (! $invoice->isAccountingDocument()) {
            return back()->with('error', $invoice->documentLabel() . ' is not a tax invoice — it carries no GST liability. Convert it to a tax invoice to post to the ledger.');
        }
        try {
            $this->posting->post($invoice, auth()->id());
            return back()->with('success', 'Invoice posted to ledger successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Invoice $invoice) {
        if ($invoice->status === 'posted') {
            return back()->with('error', 'This bill is posted — use “Reverse” to void it (it reverses the ledger entry and removes it from GST returns, payments & reports).');
        }
        $invoice->update(['status' => 'cancelled']);
        return back()->with('success', 'Invoice cancelled.');
    }

    /**
     * Reverse & void a POSTED bill (e.g. a duplicate). Posts a reversing journal
     * so the ledger nets to zero, then marks the bill cancelled with a nil balance
     * so it drops out of GST returns, pending payments and reports. The original
     * record is kept for the audit trail — never hard-deleted.
     */
    public function reverse(Invoice $invoice, Request $request) {
        $this->authorize('view', $invoice);
        if ($invoice->status !== 'posted') {
            return back()->with('error', 'Only a posted bill can be reversed. Drafts can be deleted directly.');
        }
        $reason = trim((string) $request->input('reason')) ?: 'Reversed (duplicate/void)';
        try {
            DB::transaction(function () use ($invoice, $reason) {
                if ($invoice->journal_id && $invoice->journal && ! $invoice->journal->is_reversed) {
                    $this->journalEngine->reverse($invoice->journal, now()->toDateString(), auth()->id(), $reason);
                }
                $invoice->update([
                    'status'         => 'cancelled',
                    'balance_amount' => 0,
                    'notes'          => trim(($invoice->notes ? $invoice->notes . "\n" : '') . '⤺ ' . $reason . ' on ' . now()->format('d M Y')),
                ]);
            });
            return redirect()->route('accounting.invoices.show', $invoice)
                ->with('success', 'Bill reversed & voided — the ledger entry was reversed and this bill is removed from GST returns, pending payments and reports. The record is kept for audit.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Invoice $invoice) {
        if ($invoice->isLocked()) return back()->with('error', 'Cannot delete a posted invoice.');
        $invoice->delete();
        return redirect()->route('accounting.invoices.index')->with('success', 'Invoice deleted.');
    }

    /**
     * Keep a party in the right bucket for the document it's used on. A sales
     * invoice makes the counterparty a customer; a purchase makes them a vendor.
     * If they're already the opposite, widen to 'both' (buy-from & sell-to) rather
     * than flipping — never silently drops an existing relationship.
     */
    private function alignPartyType(Party $party, string $type): void {
        $want = $type === 'sales' ? 'customer' : 'vendor';
        if ($party->type === $want || $party->type === 'both') {
            return;
        }
        $party->update(['type' => 'both']);
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

    /** Carry the original scanned file onto the invoice, from its AI suggestion. */
    private function sourceFileFrom(Request $request, int $tenantId): array {
        if (! $request->filled('ai_suggestion')) return [];
        $s = AiSuggestion::where('tenant_id', $tenantId)->where('type', 'extraction')->find($request->get('ai_suggestion'));
        $ctx = $s?->input_context ?? [];
        return array_filter([
            'source_file_path' => $ctx['file_path'] ?? null,
            'source_file_name' => $ctx['filename'] ?? null,
        ], fn ($v) => filled($v));
    }

    /** Point the AI suggestion at the invoice it produced (enables the original-file link). */
    private function linkSuggestion(Request $request, int $tenantId, int $invoiceId): void {
        if (! $request->filled('ai_suggestion')) return;
        AiSuggestion::where('tenant_id', $tenantId)->where('type', 'extraction')
            ->whereKey($request->get('ai_suggestion'))->whereNull('invoice_id')
            ->update(['invoice_id' => $invoiceId, 'status' => 'approved']);
    }

    private function nextNumber(int $tenantId, string $type, string $documentType = 'tax_invoice'): string {
        $prefix = match (true) {
            $type === 'purchase'                => 'PI',
            $documentType === 'proforma'        => 'PRO',
            $documentType === 'delivery_challan' => 'DC',
            default                             => 'SI',
        };
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
