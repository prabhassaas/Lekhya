@extends('layouts.app')
@section('title', 'Balance Sheet')
@section('page-title', 'Balance Sheet')

@section('content')
<div class="py-4 space-y-6 max-w-3xl">
    <form method="GET" class="flex items-end gap-3">
        <div><label class="block text-xs text-gray-500 mb-1">As of</label><input type="date" name="as_of" value="{{ $asOf }}" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm"></div>
        <button class="px-4 py-1.5 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">Filter</button>
        <a href="{{ route('accounting.reports.pdf', 'balance-sheet') }}?as_of={{ $asOf }}" class="ml-auto px-4 py-1.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
            <i class="fa fa-file-pdf mr-1.5"></i>Export PDF
        </a>
    </form>

    @if(abs($totalAssets - ($totalLiabilities + $totalEquity)) > 0.01)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
        <i class="fa fa-triangle-exclamation mr-1.5"></i>Assets (₹{{ number_format($totalAssets, 2) }}) don't equal Liabilities + Equity (₹{{ number_format($totalLiabilities + $totalEquity, 2) }}). Check for unposted opening balances or unbalanced journals.
    </div>
    @endif

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-3">Assets</h3>
            @forelse($assets as $a)
            <div class="flex justify-between py-1.5 text-sm border-b border-gray-50">
                <span class="text-gray-600">{{ $a['account']->name }}</span><span class="text-gray-900">₹{{ number_format($a['net'], 2) }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400">No asset accounts.</p>
            @endforelse
            <div class="flex justify-between py-2 font-semibold text-gray-900 border-t border-gray-200 mt-1">
                <span>Total Assets</span><span>₹{{ number_format($totalAssets, 2) }}</span>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Liabilities</h3>
                @forelse($liabilities as $l)
                <div class="flex justify-between py-1.5 text-sm border-b border-gray-50">
                    <span class="text-gray-600">{{ $l['account']->name }}</span><span class="text-gray-900">₹{{ number_format($l['net'], 2) }}</span>
                </div>
                @empty
                <p class="text-sm text-gray-400">No liability accounts.</p>
                @endforelse
                <div class="flex justify-between py-2 font-semibold text-gray-900 border-t border-gray-200 mt-1">
                    <span>Total Liabilities</span><span>₹{{ number_format($totalLiabilities, 2) }}</span>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Equity</h3>
                @forelse($equity as $e)
                <div class="flex justify-between py-1.5 text-sm border-b border-gray-50">
                    <span class="text-gray-600">{{ $e['account']->name }}</span><span class="text-gray-900">₹{{ number_format($e['net'], 2) }}</span>
                </div>
                @empty
                <p class="text-sm text-gray-400">No equity accounts.</p>
                @endforelse
                @if($currentEarnings != 0)
                <div class="flex justify-between py-1.5 text-sm border-b border-gray-50">
                    <span class="text-gray-600">Current Year Earnings</span><span class="text-gray-900">₹{{ number_format($currentEarnings, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between py-2 font-semibold text-gray-900 border-t border-gray-200 mt-1">
                    <span>Total Equity</span><span>₹{{ number_format($totalEquity, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
