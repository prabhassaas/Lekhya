@extends('layouts.app')
@section('title', 'GST Summary')
@section('page-title', 'GST Summary')

@section('content')
<div class="py-4 space-y-5 max-w-4xl">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <a href="{{ route('accounting.reports.index') }}" class="text-sm text-navy-600 hover:underline mr-2">← All reports</a>
        <div><label class="block text-xs text-gray-500 mb-1">From</label><input type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-gray-500 mb-1">To</label><input type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
        <button class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">Apply</button>
    </form>

    <div class="grid md:grid-cols-2 gap-5">
        {{-- Output tax --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-3"><i class="fa fa-arrow-up text-green-500 mr-1"></i>Output tax — on sales</p>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between text-gray-500"><span>Taxable value</span><span>₹{{ number_format($output['taxable'], 2) }}</span></div>
                <div class="flex justify-between text-gray-600"><span>CGST</span><span>₹{{ number_format($output['cgst'], 2) }}</span></div>
                <div class="flex justify-between text-gray-600"><span>SGST</span><span>₹{{ number_format($output['sgst'], 2) }}</span></div>
                <div class="flex justify-between text-gray-600"><span>IGST</span><span>₹{{ number_format($output['igst'], 2) }}</span></div>
                <div class="flex justify-between font-semibold text-gray-900 pt-1.5 border-t border-gray-200"><span>Total output tax</span><span>₹{{ number_format($output['total'], 2) }}</span></div>
            </div>
        </div>
        {{-- Input tax --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-3"><i class="fa fa-arrow-down text-blue-500 mr-1"></i>Input tax credit — on purchases</p>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between text-gray-500"><span>Taxable value</span><span>₹{{ number_format($input['taxable'], 2) }}</span></div>
                <div class="flex justify-between text-gray-600"><span>CGST</span><span>₹{{ number_format($input['cgst'], 2) }}</span></div>
                <div class="flex justify-between text-gray-600"><span>SGST</span><span>₹{{ number_format($input['sgst'], 2) }}</span></div>
                <div class="flex justify-between text-gray-600"><span>IGST</span><span>₹{{ number_format($input['igst'], 2) }}</span></div>
                <div class="flex justify-between font-semibold text-gray-900 pt-1.5 border-t border-gray-200"><span>Total input credit</span><span>₹{{ number_format($input['total'], 2) }}</span></div>
            </div>
        </div>
    </div>

    {{-- Net --}}
    <div class="rounded-xl border shadow-sm p-5 {{ $net >= 0 ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200' }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium {{ $net >= 0 ? 'text-amber-800' : 'text-green-800' }}">{{ $net >= 0 ? 'Net GST payable' : 'Net ITC carried forward' }}</p>
                <p class="text-xs {{ $net >= 0 ? 'text-amber-600' : 'text-green-600' }}">Output tax − input credit for the period</p>
            </div>
            <p class="text-2xl font-bold {{ $net >= 0 ? 'text-amber-800' : 'text-green-800' }}">₹{{ number_format(abs($net), 2) }}</p>
        </div>
    </div>
    <p class="text-xs text-gray-400">This is an indicative working from posted invoices. File through GSTR-3B; reconcile input credit against GSTR-2B before claiming.</p>
</div>
@endsection
