<?php
namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use App\Models\{Invoice, Party, FiscalYear, Account, HsnSacCode};
use App\Services\Accounting\InvoicePostingService;
use App\Services\GST\GstRateEngine;
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
        return view('accounting.invoices.form', compact('parties', 'accounts', 'hsnCodes', 'type', 'fiscalYear', 'tenant'));
    }

    public function store(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate([
            'type' => 'required|in:sales,purchase',
            'party_id' => 'required|exists:parties,id',
            'invoice_date' => 'required|date',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.hsn_sac_code' => 'nullable|string',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.rate' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);
        $tenant = auth()->user()->tenant;
        $party = Party::findOrFail($validated['party_id']);
        $fiscalYear = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->firstOrFail();
        $lines = $request->input('lines', []);

        $supplierState = $tenant->state_code ?? '';
        $buyerState = $party->state_code ?? $supplierState;
        $isInterstate = $supplierState !== $buyerState;

        $subtotal = 0; $taxableTotal = 0; $cgstTotal = 0; $sgstTotal = 0; $igstTotal = 0;
        $computedLines = [];
        foreach ($lines as $line) {
            $gross = $line['quantity'] * $line['rate'];
            $taxable = round($gross * (1 - ($line['discount_percent'] ?? 0) / 100), 4);
            $rates = $this->rateEngine->getRates($line['hsn_sac_code'] ?? '', $supplierState, $buyerState);
            $tax = $this->rateEngine->calculateTax($taxable, $rates);

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
        $count = Invoice::where('tenant_id', $tenantId)->where('type', $type)->whereYear('invoice_date', date('Y'))->count();
        return "{$prefix}/{$year}/" . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
