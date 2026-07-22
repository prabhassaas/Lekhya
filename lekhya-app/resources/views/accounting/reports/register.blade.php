@extends('layouts.app')
@section('title', $type === 'sales' ? 'Sales Register' : 'Purchase Register')
@section('page-title', $type === 'sales' ? 'Sales Register' : 'Purchase Register')

@section('content')
@php $partyLabel = $type === 'sales' ? 'Customer' : 'Vendor'; @endphp
<div class="py-4 space-y-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <a href="{{ route('accounting.reports.index') }}" class="text-sm text-navy-600 hover:underline mr-2">← All reports</a>
        <div><label class="block text-xs text-gray-500 mb-1">From</label><input type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-gray-500 mb-1">To</label><input type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
        <button class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">Apply</button>
        <span class="ml-auto text-sm text-gray-500">{{ $invoices->count() }} {{ Str::plural('invoice', $invoices->count()) }} · <strong class="text-gray-900">₹{{ number_format($totals['total'], 2) }}</strong></span>
    </form>

    <div class="flex justify-end">
        <x-report-share :type="$type === 'sales' ? 'sales-register' : 'purchase-register'" :filters="['from' => $from, 'to' => $to]" />
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-2.5">Date</th>
                    <th class="text-left px-4 py-2.5">Invoice #</th>
                    <th class="text-left px-4 py-2.5">{{ $partyLabel }}</th>
                    <th class="text-left px-4 py-2.5">GSTIN</th>
                    <th class="text-right px-4 py-2.5">Taxable</th>
                    <th class="text-right px-4 py-2.5">CGST</th>
                    <th class="text-right px-4 py-2.5">SGST</th>
                    <th class="text-right px-4 py-2.5">IGST</th>
                    <th class="text-right px-4 py-2.5">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($invoices as $inv)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ $inv->invoice_date->format('d M Y') }}</td>
                    <td class="px-4 py-2.5"><a href="{{ route('accounting.invoices.show', $inv) }}" class="text-navy-600 hover:underline">{{ $inv->invoice_number }}</a></td>
                    <td class="px-4 py-2.5 text-gray-800">{{ $inv->party->name ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-gray-400 font-mono text-xs">{{ $inv->party->gstin ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-right text-gray-700">₹{{ number_format($inv->taxable_amount, 2) }}</td>
                    <td class="px-4 py-2.5 text-right text-gray-500">{{ $inv->cgst_amount > 0 ? '₹'.number_format($inv->cgst_amount, 2) : '—' }}</td>
                    <td class="px-4 py-2.5 text-right text-gray-500">{{ $inv->sgst_amount > 0 ? '₹'.number_format($inv->sgst_amount, 2) : '—' }}</td>
                    <td class="px-4 py-2.5 text-right text-gray-500">{{ $inv->igst_amount > 0 ? '₹'.number_format($inv->igst_amount, 2) : '—' }}</td>
                    <td class="px-4 py-2.5 text-right font-medium text-gray-900">₹{{ number_format($inv->total_amount, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-5 py-10 text-center text-gray-400">No {{ $type }} invoices in this period.</td></tr>
                @endforelse
            </tbody>
            @if($invoices->isNotEmpty())
            <tfoot>
                <tr class="border-t-2 border-gray-200 bg-gray-50 font-semibold text-gray-900">
                    <td class="px-4 py-2.5" colspan="4">Total</td>
                    <td class="px-4 py-2.5 text-right">₹{{ number_format($totals['taxable'], 2) }}</td>
                    <td class="px-4 py-2.5 text-right">₹{{ number_format($totals['cgst'], 2) }}</td>
                    <td class="px-4 py-2.5 text-right">₹{{ number_format($totals['sgst'], 2) }}</td>
                    <td class="px-4 py-2.5 text-right">₹{{ number_format($totals['igst'], 2) }}</td>
                    <td class="px-4 py-2.5 text-right">₹{{ number_format($totals['total'], 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
        </div>
    </div>
</div>
@endsection
