<?php
namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use App\Models\{Account, Invoice, Party};
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller {
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

        $assets      = $this->getAccountBalances($tenantId, 'asset', null, $asOf);
        $liabilities = $this->getAccountBalances($tenantId, 'liability', null, $asOf);
        $equity      = $this->getAccountBalances($tenantId, 'equity', null, $asOf);

        $totalAssets      = collect($assets)->sum('net');
        $totalLiabilities = collect($liabilities)->sum('net');
        $totalEquity      = collect($equity)->sum('net');

        return view('accounting.reports.balance-sheet', compact('assets','liabilities','equity','totalAssets','totalLiabilities','totalEquity','asOf'));
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

    public function exportPdf(string $type, Request $request) {
        $data = match($type) {
            'profit-loss'  => $this->profitLoss($request)->getData(),
            'balance-sheet'=> $this->balanceSheet($request)->getData(),
            default        => [],
        };
        $pdf = Pdf::loadView("accounting.reports.{$type}-pdf", $data)->setPaper('A4');
        return $pdf->download("lekhya-{$type}-" . date('Y-m-d') . '.pdf');
    }

    private function getAccountBalances(int $tenantId, string $type, ?string $from, ?string $to, ?string $subType = null): array {
        $query = Account::where('tenant_id', $tenantId)->where('type', $type)->where('is_ledger', true);
        if ($subType) $query->where('sub_type', $subType);
        return $query->get()->map(fn($a) => ['account' => $a, 'net' => $a->getBalance($from, $to)['net']])->all();
    }

    private function agingBucket(int $days): string {
        if ($days <= 30) return '0-30';
        if ($days <= 60) return '31-60';
        if ($days <= 90) return '61-90';
        return '90+';
    }
}
