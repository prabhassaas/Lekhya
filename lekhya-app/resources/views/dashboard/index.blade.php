@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="py-4 space-y-6">
    {{-- Metric cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Sales this Month</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">₹{{ number_format($salesThisMonth, 0) }}</p>
            <p class="text-xs text-green-600 mt-1"><i class="fa fa-arrow-up"></i> GST-inclusive</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Purchases this Month</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">₹{{ number_format($purchaseThisMonth, 0) }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Outstanding AR</p>
            <p class="text-2xl font-bold text-orange-600 mt-1">₹{{ number_format($outstandingAR, 0) }}</p>
            <a href="{{ route('accounting.reports.ar') }}" class="text-xs text-blue-600 mt-1 block">View aging →</a>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Outstanding AP</p>
            <p class="text-2xl font-bold text-red-600 mt-1">₹{{ number_format($outstandingAP, 0) }}</p>
            <a href="{{ route('accounting.reports.ap') }}" class="text-xs text-blue-600 mt-1 block">View aging →</a>
        </div>
    </div>

    {{-- Alerts --}}
    @if($quarantinedCount > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 bg-amber-100 rounded-full flex items-center justify-center">
                <i class="fa fa-triangle-exclamation text-amber-600"></i>
            </div>
            <div>
                <p class="font-medium text-amber-900">{{ $quarantinedCount }} invoice(s) in review queue</p>
                <p class="text-sm text-amber-700">These could not be auto-posted. Review and approve manually.</p>
            </div>
        </div>
        <a href="{{ route('connector.queue') }}" class="px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700">Review Now</a>
    </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Recent Sales Invoices --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-semibold text-gray-900">Recent Invoices</h3>
                <a href="{{ route('accounting.invoices.index') }}" class="text-sm text-blue-600 hover:text-blue-700">View all →</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentInvoices as $inv)
                <div class="px-5 py-3 flex justify-between items-center hover:bg-gray-50">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $inv->invoice_number }}</p>
                        <p class="text-xs text-gray-500">{{ $inv->party->name ?? '—' }} · {{ $inv->invoice_date->format('d M') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">₹{{ number_format($inv->total_amount, 0) }}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $inv->status === 'posted' ? 'bg-green-100 text-green-700' :
                               ($inv->status === 'draft' ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700') }}">
                            {{ ucfirst($inv->status) }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-gray-400">
                    <i class="fa fa-file-invoice text-2xl mb-2 block"></i>
                    <p class="text-sm">No invoices yet.</p>
                    <a href="{{ route('accounting.invoices.create') }}" class="mt-2 inline-block text-blue-600 text-sm">Create your first invoice →</a>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Quick Actions</h3>
            </div>
            <div class="p-5 grid grid-cols-2 gap-3">
                <a href="{{ route('accounting.invoices.create') }}" class="quick-action bg-navy-50 hover:bg-navy-100 border border-navy-200">
                    <i class="fa fa-plus-circle text-navy-600 text-xl mb-2"></i>
                    <span class="text-navy-700 text-sm font-medium">New Invoice</span>
                </a>
                <a href="{{ route('accounting.journals.create') }}" class="quick-action bg-purple-50 hover:bg-purple-100 border border-purple-200">
                    <i class="fa fa-book-open text-purple-600 text-xl mb-2"></i>
                    <span class="text-purple-700 text-sm font-medium">New Journal</span>
                </a>
                <a href="{{ route('gst.gstr1') }}" class="quick-action bg-green-50 hover:bg-green-100 border border-green-200">
                    <i class="fa fa-landmark text-green-600 text-xl mb-2"></i>
                    <span class="text-green-700 text-sm font-medium">GSTR-1</span>
                </a>
                <a href="{{ route('banking.index') }}" class="quick-action bg-blue-50 hover:bg-blue-100 border border-blue-200">
                    <i class="fa fa-building-columns text-blue-600 text-xl mb-2"></i>
                    <span class="text-blue-700 text-sm font-medium">Bank Recon</span>
                </a>
                <a href="{{ route('ai.index') }}" class="quick-action bg-orange-50 hover:bg-orange-100 border border-orange-200">
                    <i class="fa fa-robot text-orange-600 text-xl mb-2"></i>
                    <span class="text-orange-700 text-sm font-medium">AI Extract</span>
                </a>
                <a href="{{ route('accounting.tally.index') }}" class="quick-action bg-yellow-50 hover:bg-yellow-100 border border-yellow-200">
                    <i class="fa fa-file-import text-yellow-600 text-xl mb-2"></i>
                    <span class="text-yellow-700 text-sm font-medium">Tally Import</span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.quick-action { display:flex; flex-direction:column; align-items:center; padding:1rem; border-radius:0.75rem; transition:background-color 0.15s; text-align:center; }
</style>
@endsection
