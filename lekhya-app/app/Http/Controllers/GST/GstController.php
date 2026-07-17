<?php
namespace App\Http\Controllers\GST;
use App\Http\Controllers\Controller;
use App\Models\{Invoice, Gstr1Filing, Gstr2bImport, Gstr2bReconciliation};
use App\Services\GST\{GstGateway, GstRateEngine};
use Illuminate\Http\Request;

class GstController extends Controller {
    public function __construct(private GstGateway $gateway, private GstRateEngine $rateEngine) {}

    public function dashboard() {
        $tenantId = auth()->user()->tenant_id;
        $period = request('period', date('mY'));
        return view('gst.dashboard', compact('tenantId', 'period'));
    }

    public function validateGstin(Request $request) {
        if ($request->isMethod('post') || $request->has('gstin')) {
            $gstin = $request->input('gstin');
            $result = $this->gateway->validateGstin($gstin);
            return response()->json($result);
        }
        return view('gst.validate-gstin');
    }

    public function gstr1(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $period = $request->get('period', date('mY'));
        [$month, $year] = [substr($period, 0, 2), substr($period, 2, 4)];
        $invoices = Invoice::where('tenant_id', $tenantId)->where('type', 'sales')
            ->whereMonth('invoice_date', $month)->whereYear('invoice_date', $year)
            ->where('status', 'posted')
            ->with('party', 'lines')
            ->get();
        return view('gst.gstr1', compact('invoices', 'period'));
    }

    public function generateGstr1(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $period = $request->input('period');
        $tenant = auth()->user()->tenant;
        [$month, $year] = [substr($period, 0, 2), substr($period, 2, 4)];
        $invoices = Invoice::where('tenant_id', $tenantId)->where('type', 'sales')
            ->whereMonth('invoice_date', $month)->whereYear('invoice_date', $year)
            ->where('status', 'posted')->with('party', 'lines')->get();

        $b2b = $invoices->filter(fn($i) => $i->party?->gstin)->map(fn($i) => [
            'gstin' => $i->party->gstin, 'inv' => [[
                'num' => $i->invoice_number, 'dt' => $i->invoice_date->format('d-m-Y'),
                'val' => $i->total_amount, 'pos' => $i->place_of_supply,
                'rchrg' => $i->reverse_charge ? 'Y' : 'N',
                'itms' => $i->lines->map(fn($l) => ['num' => 1, 'itm_det' => [
                    'txval' => $l->taxable_amount, 'rt' => $l->cgst_rate * 2 ?: $l->igst_rate,
                    'camt' => $l->cgst_amount, 'samt' => $l->sgst_amount, 'iamt' => $l->igst_amount,
                ]])->values()->all(),
            ]],
        ])->values()->all();

        Gstr1Filing::updateOrCreate(
            ['tenant_id' => $tenantId, 'return_period' => $period, 'gstin' => $tenant->gstin],
            ['status' => 'draft', 'b2b_data' => $b2b, 'total_taxable' => $invoices->sum('taxable_amount'), 'total_cgst' => $invoices->sum('cgst_amount'), 'total_sgst' => $invoices->sum('sgst_amount'), 'total_igst' => $invoices->sum('igst_amount')]
        );

        return back()->with('success', "GSTR-1 generated for period {$period}.");
    }

    public function fileGstr1(Request $request) {
        $period = $request->input('period');
        $tenant = auth()->user()->tenant;
        $filing = Gstr1Filing::where('tenant_id', auth()->user()->tenant_id)->where('return_period', $period)->firstOrFail();
        $result = $this->gateway->fileGstr1($tenant->gstin, $period, $filing->b2b_data ?? []);
        $filing->update(['status' => 'filed', 'filed_at' => now(), 'filed_by' => auth()->id()]);
        return back()->with('success', "GSTR-1 filed. ARN: {$result['arn']}");
    }

    public function gstr3b(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $period = $request->get('period', date('mY'));
        [$month, $year] = [substr($period, 0, 2), substr($period, 2, 4)];

        $inv = fn(string $type) => Invoice::where('tenant_id', $tenantId)->where('type', $type)
            ->whereMonth('invoice_date', $month)->whereYear('invoice_date', $year)
            ->where('status', 'posted');

        $sales     = $inv('sales')->get();
        $purchases = $inv('purchase')->get();
        $rcm       = $purchases->where('reverse_charge', true);

        $sum = fn($c, string $f) => (float) $c->sum($f);

        // 3.1(a) — Outward taxable supplies (other than zero-rated / nil / exempt)
        $outward = [
            'taxable' => $sum($sales, 'taxable_amount'),
            'igst' => $sum($sales, 'igst_amount'), 'cgst' => $sum($sales, 'cgst_amount'),
            'sgst' => $sum($sales, 'sgst_amount'), 'cess' => $sum($sales, 'cess_amount'),
        ];
        // 3.1(d) — Inward supplies liable to reverse charge
        $reverse = [
            'taxable' => $sum($rcm, 'taxable_amount'),
            'igst' => $sum($rcm, 'igst_amount'), 'cgst' => $sum($rcm, 'cgst_amount'),
            'sgst' => $sum($rcm, 'sgst_amount'), 'cess' => $sum($rcm, 'cess_amount'),
        ];
        // 4 — Eligible ITC (all posted purchases, incl. RCM which is also creditable)
        $itc = [
            'igst' => $sum($purchases, 'igst_amount'), 'cgst' => $sum($purchases, 'cgst_amount'),
            'sgst' => $sum($purchases, 'sgst_amount'), 'cess' => $sum($purchases, 'cess_amount'),
        ];
        // 6.1 — Net tax payable = (outward + RCM) − ITC, floored at 0 per head
        $net = [];
        foreach (['igst', 'cgst', 'sgst', 'cess'] as $h) {
            $net[$h] = max(0, ($outward[$h] + $reverse[$h]) - $itc[$h]);
        }

        return view('gst.gstr3b', compact('period', 'outward', 'reverse', 'itc', 'net', 'sales', 'purchases'));
    }

    public function gstr2b(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $period = $request->get('period', date('mY'));
        [$month, $year] = [substr($period, 0, 2), substr($period, 2, 4)];

        // Purchases as per your books for the period (the ITC side)
        $purchases = Invoice::where('tenant_id', $tenantId)->where('type', 'purchase')
            ->whereMonth('invoice_date', $month)->whereYear('invoice_date', $year)
            ->where('status', 'posted')->with('party')->get();

        $latestImport = Gstr2bImport::where('tenant_id', $tenantId)->where('return_period', $period)
            ->latest('imported_at')->first();

        $reconciliations = Gstr2bReconciliation::where('tenant_id', $tenantId)->where('return_period', $period)
            ->with('invoice.party')->get();

        $bookItc = [
            'count' => $purchases->count(),
            'taxable' => (float) $purchases->sum('taxable_amount'),
            'tax' => (float) ($purchases->sum('cgst_amount') + $purchases->sum('sgst_amount') + $purchases->sum('igst_amount')),
        ];

        return view('gst.gstr2b', compact('period', 'purchases', 'latestImport', 'reconciliations', 'bookItc'));
    }

    public function importGstr2b(Request $request) {
        $request->validate(['period' => 'required', 'file' => 'required|file|mimes:json|max:10240']);
        $tenantId = auth()->user()->tenant_id;
        $data = json_decode(file_get_contents($request->file('file')->getRealPath()), true);
        Gstr2bImport::create(['tenant_id' => $tenantId, 'return_period' => $request->period, 'gstin' => auth()->user()->tenant->gstin, 'data' => $data, 'imported_at' => now()]);
        return back()->with('success', 'GSTR-2B imported. Reconciliation initiated.');
    }

    public function reconcile2b(Request $request) {
        return view('gst.gstr2b-reconcile');
    }

    public function eInvoice(Invoice $invoice) {
        abort_if($invoice->tenant_id !== auth()->user()->tenant_id, 403);
        return view('gst.einvoice', compact('invoice'));
    }

    public function generateIrn(Invoice $invoice) {
        abort_if($invoice->tenant_id !== auth()->user()->tenant_id, 403);
        if ($invoice->irn) return back()->with('error', 'IRN already generated for this invoice.');
        $payload = ['invoice_number' => $invoice->invoice_number, 'total' => $invoice->total_amount, 'gstin' => $invoice->tenant->gstin];
        $result = $this->gateway->generateIrn($payload);
        $invoice->update(['irn' => $result['irn'], 'ack_number' => $result['ack_no'], 'ack_date' => $result['ack_date'], 'signed_qr' => $result['signed_qr']]);
        return back()->with('success', "IRN generated: {$result['irn']}");
    }
}
