@extends('layouts.app')
@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
@php
    $groups = [
        'Financial Statements' => [
            ['reports.pl', 'Profit &amp; Loss', 'fa-chart-line', 'Income vs expenses for a period'],
            ['reports.bs', 'Balance Sheet', 'fa-scale-balanced', 'Assets, liabilities & equity as on a date'],
            ['reports.tb', 'Trial Balance', 'fa-list-ol', 'All ledger balances — debit = credit'],
        ],
        'Books & Ledgers' => [
            ['reports.daybook', 'Day Book', 'fa-book-open', 'Every posted voucher, chronological'],
            ['accounting.accounts.index', 'General Ledger', 'fa-book', 'Any account\'s ledger & running balance'],
            ['accounting.journals.index', 'Journal Register', 'fa-file-lines', 'All journal vouchers by type'],
        ],
        'Sales & Purchase' => [
            ['reports.sales', 'Sales Register', 'fa-arrow-trend-up', 'All sales invoices with GST break-up'],
            ['reports.purchases', 'Purchase Register', 'fa-arrow-trend-down', 'All purchase bills with GST break-up'],
        ],
        'GST' => [
            ['reports.gst', 'GST Summary', 'fa-percent', 'Output vs input tax and net payable'],
            ['gst.gstr1', 'GSTR-1', 'fa-file-invoice', 'Outward supplies return'],
            ['gst.gstr3b', 'GSTR-3B', 'fa-file-invoice-dollar', 'Monthly summary return'],
            ['gst.gstr2b', 'GSTR-2B / ITC', 'fa-arrows-rotate', 'Input tax credit reconciliation'],
        ],
        'Receivables & Payables' => [
            ['reports.party', 'Party Statement', 'fa-user-tag', 'A customer/vendor ledger with running balance'],
            ['reports.ar', 'AR Ageing', 'fa-hourglass-half', 'Receivables by overdue bucket'],
            ['reports.ap', 'AP Ageing', 'fa-hourglass-end', 'Payables by overdue bucket'],
            ['accounting.payments.pending', 'Pending Payments', 'fa-money-bill-wave', 'Outstanding to receive / pay'],
        ],
        'Cash & Bank' => [
            ['accounting.payments.history', 'Receipts & Payments', 'fa-hand-holding-dollar', 'All settlements recorded'],
            ['banking.index', 'Bank Reconciliation', 'fa-building-columns', 'Match statements to the books'],
        ],
    ];
@endphp
<div class="py-4 space-y-6">
    @foreach($groups as $group => $reports)
    <div>
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $group }}</h3>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($reports as [$route, $label, $icon, $desc])
            @if(Route::has($route))
            <a href="{{ route($route) }}" class="flex items-start gap-3 bg-white rounded-xl border border-gray-100 shadow-sm p-4 hover:border-navy-300 hover:shadow transition">
                <div class="w-9 h-9 rounded-lg bg-navy-50 text-navy-600 flex items-center justify-center shrink-0"><i class="fa {{ $icon }}"></i></div>
                <div class="min-w-0">
                    <p class="font-medium text-gray-900">{!! $label !!}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $desc }}</p>
                </div>
            </a>
            @endif
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection
