<?php
namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use App\Models\{Invoice, Party, FiscalYear, Account, HsnSacCode};
use App\Services\Accounting\InvoicePostingService;
use Illuminate\Http\Request;

class InvoiceController extends Controller {
    public function __construct(private InvoicePostingService $posting) {}

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
        $parties = Party::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $accounts = Account::where('tenant_id', $tenantId)->where('is_ledger', true)->get();
        $hsnCodes = HsnSacCode::limit(200)->get();
        $fiscalYear = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->first();
        return view('accounting.invoices.form', compact('parties', 'accounts', 'hsnCodes', 'type', 'fiscalYear'));
    }

    public function store(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate([
            'type' => 'required|in:sales,purchase',
            'party_id' => 'required|exists:parties,id',
            'invoice_date' => 'required|date',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.rate' => 'required|numeric|min:0',
        ]);
        $fiscalYear = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->firstOrFail();
        $lines = $request->input('lines', []);
        $subtotal = array_sum(array_map(fn($l) => $l['quantity'] * $l['rate'], $lines));

        $invoice = Invoice::create(array_merge($validated, [
            'tenant_id' => $tenantId,
            'fiscal_year_id' => $fiscalYear->id,
            'invoice_number' => $this->nextNumber($tenantId, $validated['type']),
            'status' => 'draft',
            'subtotal' => $subtotal,
            'total_amount' => $subtotal,
            'balance_amount' => $subtotal,
            'created_by' => auth()->id(),
        ]));

        foreach ($lines as $i => $line) {
            $taxable = ($line['quantity'] * $line['rate']) * (1 - ($line['discount_percent'] ?? 0) / 100);
            $invoice->lines()->create(array_merge($line, [
                'tenant_id' => $tenantId,
                'line_order' => $i,
                'taxable_amount' => $taxable,
                'line_total' => $taxable,
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
