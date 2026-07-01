<?php

namespace App\Http\Controllers;

use App\Models\ConnectorImportQueue;
use App\Models\Invoice;
use App\Models\Journal;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $tenant = auth()->user()->tenant;
        $now = now();

        // Key metrics
        $salesThisMonth = Invoice::where('tenant_id', $tenantId)
            ->where('type', 'sales')
            ->whereMonth('invoice_date', $now->month)
            ->whereYear('invoice_date', $now->year)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $purchaseThisMonth = Invoice::where('tenant_id', $tenantId)
            ->where('type', 'purchase')
            ->whereMonth('invoice_date', $now->month)
            ->whereYear('invoice_date', $now->year)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $outstandingAR = Invoice::where('tenant_id', $tenantId)
            ->whereIn('type', ['sales'])
            ->whereIn('status', ['posted', 'partially_paid'])
            ->sum('balance_amount');

        $outstandingAP = Invoice::where('tenant_id', $tenantId)
            ->whereIn('type', ['purchase'])
            ->whereIn('status', ['posted', 'partially_paid'])
            ->sum('balance_amount');

        $quarantinedCount = ConnectorImportQueue::where('tenant_id', $tenantId)
            ->where('status', 'quarantined')->count();

        $recentInvoices = Invoice::where('tenant_id', $tenantId)
            ->with('party')
            ->latest('invoice_date')
            ->limit(5)
            ->get();

        $recentJournals = Journal::where('tenant_id', $tenantId)
            ->latest('date')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'tenant', 'salesThisMonth', 'purchaseThisMonth',
            'outstandingAR', 'outstandingAP', 'quarantinedCount',
            'recentInvoices', 'recentJournals'
        ));
    }
}
