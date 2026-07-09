@extends('layouts.app')
@section('title', 'GSTR-1')
@section('page-title', 'GSTR-1 — Outward Supplies')

@section('content')
@php
    $b2b = $invoices->filter(fn($i) => $i->party?->gstin);
    $b2c = $invoices->reject(fn($i) => $i->party?->gstin);
    $taxable = $invoices->sum('taxable_amount');
    $cgst = $invoices->sum('cgst_amount');
    $sgst = $invoices->sum('sgst_amount');
    $igst = $invoices->sum('igst_amount');
@endphp
<div class="py-4 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-gray-500">Period</label>
            <input type="month" name="period_input" value="{{ substr($period, 2, 4) . '-' . substr($period, 0, 2) }}"
                   onchange="this.form.period.value = this.value.slice(5,7) + this.value.slice(0,4); this.form.submit()"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
            <input type="hidden" name="period" value="{{ $period }}">
        </form>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('gst.gstr1.generate') }}">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <button class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
                    <i class="fa fa-file-export mr-1.5"></i>Generate Return
                </button>
            </form>
            <form method="POST" action="{{ route('gst.gstr1.file') }}">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">
                    <i class="fa fa-paper-plane mr-1.5"></i>File with GSTN
                </button>
            </form>
        </div>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Invoices</p>
            <p class="text-xl font-bold text-gray-900 mt-1">{{ $invoices->count() }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Taxable Value</p>
            <p class="text-xl font-bold text-gray-900 mt-1">₹{{ number_format($taxable, 0) }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">CGST</p>
            <p class="text-xl font-bold text-navy-600 mt-1">₹{{ number_format($cgst, 0) }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">SGST</p>
            <p class="text-xl font-bold text-navy-600 mt-1">₹{{ number_format($sgst, 0) }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">IGST</p>
            <p class="text-xl font-bold text-navy-600 mt-1">₹{{ number_format($igst, 0) }}</p>
        </div>
    </div>

    {{-- B2B --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">B2B Invoices</h3>
            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $b2b->count() }}</span>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Invoice #</th>
                    <th class="text-left px-5 py-2.5">Party</th>
                    <th class="text-left px-5 py-2.5">GSTIN</th>
                    <th class="text-left px-5 py-2.5">Date</th>
                    <th class="text-right px-5 py-2.5">Taxable</th>
                    <th class="text-right px-5 py-2.5">Tax</th>
                    <th class="text-right px-5 py-2.5">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($b2b as $inv)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-900">{{ $inv->invoice_number }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $inv->party->name }}</td>
                    <td class="px-5 py-3 text-gray-500 font-mono text-xs">{{ $inv->party->gstin }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $inv->invoice_date->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">₹{{ number_format($inv->taxable_amount, 0) }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">₹{{ number_format($inv->cgst_amount + $inv->sgst_amount + $inv->igst_amount, 0) }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-gray-900">₹{{ number_format($inv->total_amount, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-8 text-center text-gray-400">No B2B invoices for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- B2C --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">B2C Invoices</h3>
            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $b2c->count() }}</span>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($b2c as $inv)
            <div class="px-5 py-3 flex justify-between items-center hover:bg-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $inv->invoice_number }}</p>
                    <p class="text-xs text-gray-500">{{ $inv->party->name ?? 'Walk-in' }} · {{ $inv->invoice_date->format('d M') }}</p>
                </div>
                <p class="text-sm font-semibold text-gray-900">₹{{ number_format($inv->total_amount, 0) }}</p>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-400">No B2C invoices for this period.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
