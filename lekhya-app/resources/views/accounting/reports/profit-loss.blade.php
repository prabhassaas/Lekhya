@extends('layouts.app')
@section('title', 'Profit & Loss')
@section('page-title', 'Profit & Loss')

@section('content')
<div class="py-4 space-y-6 max-w-3xl">
    <form method="GET" class="flex items-end gap-3">
        <div><label class="block text-xs text-gray-500 mb-1">From</label><input type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm"></div>
        <div><label class="block text-xs text-gray-500 mb-1">To</label><input type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm"></div>
        <button class="px-4 py-1.5 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">Filter</button>
        <div class="ml-auto"><x-report-share type="profit-loss" :filters="['from' => $from, 'to' => $to]" /></div>
    </form>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-6">
        <div>
            <h3 class="font-semibold text-gray-900 mb-3">Revenue</h3>
            @forelse($revenues as $r)
            <div class="flex justify-between py-1.5 text-sm border-b border-gray-50">
                <span class="text-gray-600">{{ $r['account']->name }}</span>
                <span class="text-gray-900">₹{{ number_format($r['net'], 2) }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400">No revenue accounts.</p>
            @endforelse
            <div class="flex justify-between py-2 font-semibold text-gray-900 border-t border-gray-200 mt-1">
                <span>Total Revenue</span><span>₹{{ number_format($totalRevenue, 2) }}</span>
            </div>
        </div>

        @if($totalCogs > 0)
        <div>
            <h3 class="font-semibold text-gray-900 mb-3">Cost of Goods Sold</h3>
            @foreach($cogs as $c)
            <div class="flex justify-between py-1.5 text-sm border-b border-gray-50">
                <span class="text-gray-600">{{ $c['account']->name }}</span><span class="text-gray-900">₹{{ number_format($c['net'], 2) }}</span>
            </div>
            @endforeach
            <div class="flex justify-between py-2 font-semibold text-gray-900 border-t border-gray-200 mt-1">
                <span>Gross Profit</span><span>₹{{ number_format($grossProfit, 2) }}</span>
            </div>
        </div>
        @endif

        <div>
            <h3 class="font-semibold text-gray-900 mb-3">Operating Expenses</h3>
            @forelse($expenses as $e)
            @if($e['account']->sub_type !== 'cost_of_sales')
            <div class="flex justify-between py-1.5 text-sm border-b border-gray-50">
                <span class="text-gray-600">{{ $e['account']->name }}</span><span class="text-gray-900">₹{{ number_format($e['net'], 2) }}</span>
            </div>
            @endif
            @empty
            <p class="text-sm text-gray-400">No expense accounts.</p>
            @endforelse
            <div class="flex justify-between py-2 font-semibold text-gray-900 border-t border-gray-200 mt-1">
                <span>Total Expenses</span><span>₹{{ number_format($totalExpenses, 2) }}</span>
            </div>
        </div>

        <div class="flex justify-between py-3 px-4 rounded-xl {{ $netProfit >= 0 ? 'bg-green-50' : 'bg-red-50' }} font-bold text-lg">
            <span class="{{ $netProfit >= 0 ? 'text-green-800' : 'text-red-800' }}">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</span>
            <span class="{{ $netProfit >= 0 ? 'text-green-800' : 'text-red-800' }}">₹{{ number_format(abs($netProfit), 2) }}</span>
        </div>
    </div>
</div>
@endsection
