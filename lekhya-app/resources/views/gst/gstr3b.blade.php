@extends('layouts.app')
@section('title', 'GSTR-3B')
@section('page-title', 'GSTR-3B — Summary Return')

@section('content')
@php
    $payable = ($outward['igst'] + $outward['cgst'] + $outward['sgst'] + $outward['cess'])
             + ($reverse['igst'] + $reverse['cgst'] + $reverse['sgst'] + $reverse['cess']);
    $itcTotal = $itc['igst'] + $itc['cgst'] + $itc['sgst'] + $itc['cess'];
    $netTotal = $net['igst'] + $net['cgst'] + $net['sgst'] + $net['cess'];
    $money = fn($v) => '₹' . number_format((float) $v, 2);
@endphp
<div class="py-4 space-y-6">

    {{-- Header / period --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-gray-500">Period</label>
            <input type="month" value="{{ substr($period, 2, 4) . '-' . substr($period, 0, 2) }}"
                   onchange="this.form.period.value = this.value.slice(5,7) + this.value.slice(0,4); this.form.submit()"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
            <input type="hidden" name="period" value="{{ $period }}">
        </form>
        <span class="text-xs px-3 py-1.5 rounded-full font-medium bg-navy-50 text-navy-700 border border-navy-100">
            Auto-computed from posted invoices · GSTIN {{ auth()->user()->tenant->gstin ?? 'not set' }}
        </span>
    </div>

    {{-- Headline numbers --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Output Tax (incl. RCM)</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $money($payable) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $sales->count() }} sales · {{ $purchases->where('reverse_charge', true)->count() }} RCM bills</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Eligible ITC</p>
            <p class="text-2xl font-bold text-teal-600 mt-1">{{ $money($itcTotal) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $purchases->count() }} purchase bills</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-navy-100 shadow-sm ring-1 ring-navy-100">
            <p class="text-xs text-navy-500 font-medium uppercase tracking-wider">Net Tax Payable (in cash)</p>
            <p class="text-2xl font-bold text-navy-700 mt-1">{{ $money($netTotal) }}</p>
            <p class="text-xs text-gray-400 mt-1">Output − ITC, per tax head</p>
        </div>
    </div>

    {{-- 3.1 Outward & RCM --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-900 text-sm">3.1 — Details of Outward Supplies &amp; Inward Supplies on Reverse Charge</h3></div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Nature of supply</th>
                    <th class="text-right px-5 py-2.5">Taxable value</th>
                    <th class="text-right px-5 py-2.5">IGST</th>
                    <th class="text-right px-5 py-2.5">CGST</th>
                    <th class="text-right px-5 py-2.5">SGST</th>
                    <th class="text-right px-5 py-2.5">Cess</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 text-gray-700">
                <tr>
                    <td class="px-5 py-3">(a) Outward taxable supplies (other than zero-rated, nil &amp; exempted)</td>
                    <td class="px-5 py-3 text-right">{{ $money($outward['taxable']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($outward['igst']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($outward['cgst']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($outward['sgst']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($outward['cess']) }}</td>
                </tr>
                <tr>
                    <td class="px-5 py-3">(d) Inward supplies liable to reverse charge</td>
                    <td class="px-5 py-3 text-right">{{ $money($reverse['taxable']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($reverse['igst']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($reverse['cgst']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($reverse['sgst']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($reverse['cess']) }}</td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>

    {{-- 4 Eligible ITC --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-900 text-sm">4 — Eligible Input Tax Credit</h3></div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Details</th>
                    <th class="text-right px-5 py-2.5">IGST</th>
                    <th class="text-right px-5 py-2.5">CGST</th>
                    <th class="text-right px-5 py-2.5">SGST</th>
                    <th class="text-right px-5 py-2.5">Cess</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 text-gray-700">
                <tr>
                    <td class="px-5 py-3">(A) ITC available — inward supplies (incl. reverse charge)</td>
                    <td class="px-5 py-3 text-right">{{ $money($itc['igst']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($itc['cgst']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($itc['sgst']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($itc['cess']) }}</td>
                </tr>
                <tr class="bg-teal-50/40 font-medium">
                    <td class="px-5 py-3">(C) Net ITC available</td>
                    <td class="px-5 py-3 text-right">{{ $money($itc['igst']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($itc['cgst']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($itc['sgst']) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($itc['cess']) }}</td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>

    {{-- 6.1 Tax payable --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-900 text-sm">6.1 — Net Tax Payable (in cash)</h3></div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Head</th>
                    <th class="text-right px-5 py-2.5">Output tax</th>
                    <th class="text-right px-5 py-2.5">ITC</th>
                    <th class="text-right px-5 py-2.5">Payable in cash</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 text-gray-700">
                @foreach(['igst' => 'IGST', 'cgst' => 'CGST', 'sgst' => 'SGST', 'cess' => 'Cess'] as $h => $label)
                <tr>
                    <td class="px-5 py-3 font-medium">{{ $label }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($outward[$h] + $reverse[$h]) }}</td>
                    <td class="px-5 py-3 text-right text-teal-600">− {{ $money($itc[$h]) }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-navy-700">{{ $money($net[$h]) }}</td>
                </tr>
                @endforeach
                <tr class="bg-navy-50/50 font-bold text-gray-900">
                    <td class="px-5 py-3">Total</td>
                    <td class="px-5 py-3 text-right">{{ $money($payable) }}</td>
                    <td class="px-5 py-3 text-right text-teal-700">− {{ $money($itcTotal) }}</td>
                    <td class="px-5 py-3 text-right text-navy-700">{{ $money($netTotal) }}</td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 flex items-start gap-2">
        <i class="fa fa-circle-info mt-0.5"></i>
        <span>This 3B summary is auto-computed from your posted sales &amp; purchase invoices for the period. Review against your books before filing on the GST portal. ITC is shown gross of any ineligible/blocked credit — adjust on the portal where applicable.</span>
    </p>
</div>
@endsection
