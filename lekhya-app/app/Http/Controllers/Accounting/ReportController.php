<?php
namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use App\Models\{Account, Invoice, Journal, Party, Payment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller {

    /** Reports hub — grouped catalogue of every report. */
    public function index() {
        return view('accounting.reports.index');
    }

    /** Day Book — every posted voucher in a date range, chronological. */
    public function dayBook(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $from = $request->get('from', date('Y-m-01'));
        $to   = $request->get('to', date('Y-m-d'));
        $journals = Journal::where('tenant_id', $tenantId)->where('is_posted', true)
            ->whereBetween('date', [$from, $to])->with(['lines.account'])
            ->orderBy('date')->orderBy('id')->get();
        $totalDebit = $journals->sum('total_debit');
        return view('accounting.reports.day-book', compact('journals', 'from', 'to', 'totalDebit'));
    }

    public function salesRegister(Request $request)   { return $this->register($request, 'sales'); }
    public function purchaseRegister(Request $request){ return $this->register($request, 'purchase'); }

    private function register(Request $request, string $type) {
        $tenantId = auth()->user()->tenant_id;
        $from = $request->get('from', date('Y-04-01'));
        $to   = $request->get('to', date('Y-m-d'));
        $invoices = Invoice::where('tenant_id', $tenantId)->where('type', $type)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('invoice_date', [$from, $to])->with('party')
            ->orderBy('invoice_date')->orderBy('id')->get();
        $totals = [
            'taxable' => (float) $invoices->sum('taxable_amount'),
            'cgst'    => (float) $invoices->sum('cgst_amount'),
            'sgst'    => (float) $invoices->sum('sgst_amount'),
            'igst'    => (float) $invoices->sum('igst_amount'),
            'total'   => (float) $invoices->sum('total_amount'),
        ];
        return view('accounting.reports.register', compact('invoices', 'totals', 'from', 'to', 'type'));
    }

    /** GST Summary — output tax (sales) vs input tax (purchases) and net payable. */
    public function gstSummary(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $from = $request->get('from', date('Y-m-01'));
        $to   = $request->get('to', date('Y-m-d'));
        $base = fn(string $t) => Invoice::where('tenant_id', $tenantId)->where('type', $t)
            ->whereNotIn('status', ['draft', 'cancelled'])->whereBetween('invoice_date', [$from, $to]);
        $mk = function ($q) {
            $cgst = (float) (clone $q)->sum('cgst_amount');
            $sgst = (float) (clone $q)->sum('sgst_amount');
            $igst = (float) (clone $q)->sum('igst_amount');
            return ['cgst' => $cgst, 'sgst' => $sgst, 'igst' => $igst, 'total' => $cgst + $sgst + $igst,
                    'taxable' => (float) (clone $q)->sum('taxable_amount')];
        };
        $output = $mk($base('sales'));
        $input  = $mk($base('purchase'));
        $net    = $output['total'] - $input['total'];
        return view('accounting.reports.gst-summary', compact('output', 'input', 'net', 'from', 'to'));
    }

    /** Party Statement — a party's ledger (invoices + settlements) with running balance. */
    public function partyStatement(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $parties  = Party::where('tenant_id', $tenantId)->orderBy('name')->get();
        $party    = $request->filled('party_id') ? Party::where('tenant_id', $tenantId)->find($request->get('party_id')) : null;
        $from = $request->get('from', date('Y-04-01'));
        $to   = $request->get('to', date('Y-m-d'));

        $rows = collect();
        if ($party) {
            foreach (Invoice::where('tenant_id', $tenantId)->where('party_id', $party->id)
                        ->whereNotIn('status', ['draft', 'cancelled'])->whereBetween('invoice_date', [$from, $to])->get() as $inv) {
                $sale = $inv->type === 'sales';
                $rows->push(['date' => $inv->invoice_date->format('Y-m-d'), 'ref' => $inv->invoice_number,
                    'particulars' => ($sale ? 'Sales invoice' : 'Purchase bill'),
                    'debit' => $sale ? (float) $inv->total_amount : 0.0, 'credit' => $sale ? 0.0 : (float) $inv->total_amount]);
            }
            foreach (Payment::where('tenant_id', $tenantId)->where('party_id', $party->id)
                        ->whereBetween('date', [$from, $to])->get() as $p) {
                $receipt = $p->type === 'receipt';
                $rows->push(['date' => $p->date->format('Y-m-d'), 'ref' => $p->reference_number, 'particulars' => $p->label(),
                    'debit' => $receipt ? 0.0 : (float) $p->amount, 'credit' => $receipt ? (float) $p->amount : 0.0]);
            }
            $bal = 0.0;
            $rows = $rows->sortBy('date')->values()->map(function ($r) use (&$bal) {
                $bal += $r['debit'] - $r['credit'];
                $r['balance'] = $bal;
                return $r;
            });
        }
        return view('accounting.reports.party-statement', compact('parties', 'party', 'rows', 'from', 'to'));
    }

    public function profitLoss(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $from = $request->get('from', date('Y-04-01'));
        $to   = $request->get('to', date('Y-m-d'));

        $revenues = $this->getAccountBalances($tenantId, 'revenue', $from, $to);
        $expenses = $this->getAccountBalances($tenantId, 'expense', $from, $to);
        $cogs     = $this->getAccountBalances($tenantId, 'expense', $from, $to, 'cost_of_sales');

        $totalRevenue   = collect($revenues)->sum('net');
        $totalCogs      = collect($cogs)->sum('net');
        $grossProfit    = $totalRevenue - $totalCogs;
        $totalExpenses  = collect($expenses)->sum('net') - $totalCogs;
        $netProfit      = $grossProfit - $totalExpenses;

        return view('accounting.reports.profit-loss', compact('revenues','expenses','cogs','totalRevenue','totalCogs','grossProfit','totalExpenses','netProfit','from','to'));
    }

    public function balanceSheet(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $asOf = $request->get('as_of', date('Y-m-d'));
        $fyStartYear = Carbon::parse($asOf)->month >= 4 ? Carbon::parse($asOf)->year : Carbon::parse($asOf)->year - 1;
        $fyStart = "{$fyStartYear}-04-01";

        $assets      = $this->getAccountBalances($tenantId, 'asset', null, $asOf);
        $liabilities = $this->getAccountBalances($tenantId, 'liability', null, $asOf);
        $equity      = $this->getAccountBalances($tenantId, 'equity', null, $asOf);

        // Revenue/expense accounts don't carry into the balance sheet directly — their
        // net for the fiscal year to date rolls up into equity as unclosed current earnings.
        $revenueTotal    = collect($this->getAccountBalances($tenantId, 'revenue', $fyStart, $asOf))->sum('net');
        $expenseTotal    = collect($this->getAccountBalances($tenantId, 'expense', $fyStart, $asOf))->sum('net');
        $currentEarnings = $revenueTotal - $expenseTotal;

        $totalAssets      = collect($assets)->sum('net');
        $totalLiabilities = collect($liabilities)->sum('net');
        $totalEquity      = collect($equity)->sum('net') + $currentEarnings;

        return view('accounting.reports.balance-sheet', compact('assets','liabilities','equity','totalAssets','totalLiabilities','totalEquity','asOf','currentEarnings'));
    }

    public function trialBalance(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $asOf = $request->get('as_of', date('Y-m-d'));

        $accounts = Account::where('tenant_id', $tenantId)->where('is_ledger', true)->get()
            ->map(fn($a) => array_merge($a->toArray(), $a->getBalance(to: $asOf)));

        return view('accounting.reports.trial-balance', compact('accounts', 'asOf'));
    }

    public function arAging(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $invoices = Invoice::where('tenant_id', $tenantId)
            ->where('type', 'sales')
            ->whereIn('status', ['posted', 'partially_paid'])
            ->with('party')
            ->get()
            ->map(fn($inv) => array_merge($inv->toArray(), [
                'days_outstanding' => now()->diffInDays($inv->invoice_date),
                'bucket' => $this->agingBucket(now()->diffInDays($inv->invoice_date)),
            ]));
        return view('accounting.reports.ar-aging', compact('invoices'));
    }

    public function apAging(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $invoices = Invoice::where('tenant_id', $tenantId)
            ->where('type', 'purchase')
            ->whereIn('status', ['posted', 'partially_paid'])
            ->with('party')
            ->get()
            ->map(fn($inv) => array_merge($inv->toArray(), [
                'days_outstanding' => now()->diffInDays($inv->invoice_date),
                'bucket' => $this->agingBucket(now()->diffInDays($inv->invoice_date)),
            ]));
        return view('accounting.reports.ap-aging', compact('invoices'));
    }

    /** type → [data view-model, pdf blade view, human title]. */
    private function pdfSpec(string $type, Request $request): array {
        return match($type) {
            'profit-loss'       => [$this->profitLoss($request)->getData(),       'profit-loss',     'Profit & Loss'],
            'balance-sheet'     => [$this->balanceSheet($request)->getData(),      'balance-sheet',   'Balance Sheet'],
            'trial-balance'     => [$this->trialBalance($request)->getData(),      'trial-balance',   'Trial Balance'],
            'ar-aging'          => [$this->arAging($request)->getData(),           'ar-aging',        'AR Ageing'],
            'ap-aging'          => [$this->apAging($request)->getData(),           'ap-aging',        'AP Ageing'],
            'day-book'          => [$this->dayBook($request)->getData(),           'day-book',        'Day Book'],
            'sales-register'    => [$this->salesRegister($request)->getData(),     'register',        'Sales Register'],
            'purchase-register' => [$this->purchaseRegister($request)->getData(),  'register',        'Purchase Register'],
            'gst-summary'       => [$this->gstSummary($request)->getData(),        'gst-summary',     'GST Summary'],
            'party-statement'   => [$this->partyStatement($request)->getData(),    'party-statement', 'Party Statement'],
            default             => abort(404),
        };
    }

    /** Build the PDF for a report type. Returns [Pdf, filename, title]. */
    private function buildPdf(string $type, Request $request): array {
        [$data, $view, $title] = $this->pdfSpec($type, $request);
        $data['tenant']      = auth()->user()->tenant;
        $data['generatedAt'] = now();
        $data['reportType']  = $type;

        $pdf = Pdf::loadView("accounting.reports.pdf.{$view}", $data)->setPaper('A4');
        return [$pdf, "lekhya-{$type}-" . date('Y-m-d') . '.pdf', $title];
    }

    public function exportPdf(string $type, Request $request) {
        [$pdf, $filename] = $this->buildPdf($type, $request);
        return $pdf->download($filename);
    }

    /** Email a report as a PDF, or WhatsApp a "ready" notice, to a customer/vendor. */
    public function sendReport(string $type, Request $request) {
        $user = auth()->user();
        // Only roles above read-only may send documents outside the workspace.
        abort_unless($user->hasAnyRole(['owner', 'accountant', 'ca']), 403, 'You do not have permission to send reports externally.');

        // NB: recipient is 'recipient', not 'to' — 'to' is the report's end-date filter.
        $data = $request->validate([
            'channel'   => 'required|in:email,whatsapp',
            'recipient' => 'required_if:channel,email|nullable|email',
            'phone'     => 'required_if:channel,whatsapp|nullable|string|max:20',
            'message'   => 'nullable|string|max:500',
        ]);

        [$pdf, $filename, $title] = $this->buildPdf($type, $request);
        $company = $user->tenant->name ?? config('app.name');

        if ($data['channel'] === 'email') {
            try {
                Mail::to($data['recipient'])->send(new \App\Mail\ReportMail(
                    title: $title,
                    company: $company,
                    period: $this->periodLabel($request),
                    note: $data['message'] ?? null,
                    pdf: $pdf->output(),
                    filename: $filename,
                ));
            } catch (\Throwable $e) {
                return back()->with('error', 'Could not send the email — ' . $e->getMessage());
            }
            return back()->with('success', "“{$title}” emailed to {$data['recipient']}.");
        }

        // WhatsApp: PDFs need a hosted media URL + a live API, so we send an
        // approved "ready" template; the document itself goes by email.
        $ok = app(\App\Services\Notification\WhatsAppService::class)->sendReportReady($data['phone'], $company, $title);
        return back()->with($ok ? 'success' : 'error',
            $ok ? "WhatsApp notification sent for “{$title}”." : 'WhatsApp isn’t configured yet — send by email instead.');
    }

    /** Human-readable period for the current report filters. */
    private function periodLabel(Request $request): string {
        if ($request->filled('as_of')) {
            return 'As of ' . Carbon::parse($request->get('as_of'))->format('d M Y');
        }
        $from = $request->get('from');
        $to   = $request->get('to');
        if ($from || $to) {
            return Carbon::parse($from ?: date('Y-04-01'))->format('d M Y') . ' — ' . Carbon::parse($to ?: date('Y-m-d'))->format('d M Y');
        }
        return '';
    }

    private function getAccountBalances(int $tenantId, string $type, ?string $from, ?string $to, ?string $subType = null): array {
        $query = Account::where('tenant_id', $tenantId)->where('type', $type)->where('is_ledger', true);
        if ($subType) $query->where('sub_type', $subType);
        // getBalance() returns raw (debit - credit). Liability/equity/revenue accounts are
        // credit-normal, so flip the sign to show their natural balance as a positive figure.
        $creditNormal = in_array($type, ['liability', 'equity', 'revenue']);
        return $query->get()->map(function ($a) use ($from, $to, $creditNormal) {
            $net = $a->getBalance($from, $to)['net'];
            return ['account' => $a, 'net' => $creditNormal ? -$net : $net];
        })->all();
    }

    private function agingBucket(int $days): string {
        if ($days <= 30) return '0-30';
        if ($days <= 60) return '31-60';
        if ($days <= 90) return '61-90';
        return '90+';
    }
}
