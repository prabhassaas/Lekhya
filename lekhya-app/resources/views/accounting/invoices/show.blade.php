@extends('layouts.app')
@section('title', $invoice->invoice_number)
@section('page-title', $invoice->invoice_number)

@section('content')
<div class="py-4 space-y-6 max-w-4xl">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-xs px-2.5 py-1 rounded-full font-medium capitalize
                {{ $invoice->status === 'posted' ? 'bg-green-100 text-green-700' :
                   ($invoice->status === 'draft' ? 'bg-gray-100 text-gray-600' :
                   ($invoice->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700')) }}">
                {{ $invoice->status }}
            </span>
            <span class="text-gray-500 text-sm">{{ $invoice->invoice_date->format('d M Y') }}</span>
            @if($invoice->irn)<span class="text-xs px-2.5 py-1 rounded-full font-medium bg-navy-50 text-navy-700"><i class="fa fa-qrcode mr-1"></i>e-Invoice generated</span>@endif
        </div>
        <div class="flex gap-2">
            @if($invoice->status === 'draft')
            <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                <i class="fa fa-pen mr-1.5"></i>Edit
            </a>
            <form method="POST" action="{{ route('accounting.invoices.post', $invoice) }}" onsubmit="return confirm('Post this invoice to the ledger? This cannot be undone.');">
                @csrf
                <button class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
                    <i class="fa fa-check mr-1.5"></i>Post to Ledger
                </button>
            </form>
            <form method="POST" action="{{ route('accounting.invoices.cancel', $invoice) }}" onsubmit="return confirm('Cancel this invoice?');">
                @csrf
                <button class="px-4 py-2 border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50">Cancel</button>
            </form>
            @endif
            @if($invoice->status === 'posted' && $invoice->type === 'sales' && !$invoice->irn)
            <a href="{{ route('gst.einvoice', $invoice) }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                <i class="fa fa-qrcode mr-1.5"></i>Generate e-Invoice
            </a>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 grid grid-cols-2 gap-6">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">{{ $invoice->type === 'sales' ? 'Customer' : 'Vendor' }}</p>
            <p class="font-semibold text-gray-900">{{ $invoice->party->name ?? '—' }}</p>
            @if($invoice->party?->gstin)<p class="text-sm text-gray-500 font-mono">{{ $invoice->party->gstin }}</p>@endif
            @if($invoice->party?->address)<p class="text-sm text-gray-500">{{ $invoice->party->address }}, {{ $invoice->party->city }}</p>@endif
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Place of Supply</p>
            <p class="font-medium text-gray-900">{{ $invoice->place_of_supply ?: '—' }} · {{ $invoice->is_interstate ? 'Interstate (IGST)' : 'Intrastate (CGST+SGST)' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Description</th>
                    <th class="text-left px-5 py-2.5">HSN/SAC</th>
                    <th class="text-right px-5 py-2.5">Qty</th>
                    <th class="text-right px-5 py-2.5">Rate</th>
                    <th class="text-right px-5 py-2.5">Taxable</th>
                    <th class="text-right px-5 py-2.5">Tax</th>
                    <th class="text-right px-5 py-2.5">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($invoice->lines as $line)
                <tr>
                    <td class="px-5 py-3 text-gray-900">{{ $line->description }}</td>
                    <td class="px-5 py-3 text-gray-400 font-mono text-xs">{{ $line->hsn_sac_code ?: '—' }}</td>
                    <td class="px-5 py-3 text-right text-gray-500">{{ rtrim(rtrim(number_format($line->quantity, 3), '0'), '.') }}</td>
                    <td class="px-5 py-3 text-right text-gray-500">₹{{ number_format($line->rate, 2) }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">₹{{ number_format($line->taxable_amount, 2) }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">₹{{ number_format($line->cgst_amount + $line->sgst_amount + $line->igst_amount, 2) }}</td>
                    <td class="px-5 py-3 text-right font-medium text-gray-900">₹{{ number_format($line->line_total + $line->cgst_amount + $line->sgst_amount + $line->igst_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-5 border-t border-gray-100 flex justify-end">
            <div class="w-64 space-y-1.5 text-sm">
                <div class="flex justify-between text-gray-500"><span>Taxable Value</span><span>₹{{ number_format($invoice->taxable_amount, 2) }}</span></div>
                @if($invoice->cgst_amount > 0)
                <div class="flex justify-between text-gray-500"><span>CGST</span><span>₹{{ number_format($invoice->cgst_amount, 2) }}</span></div>
                <div class="flex justify-between text-gray-500"><span>SGST</span><span>₹{{ number_format($invoice->sgst_amount, 2) }}</span></div>
                @endif
                @if($invoice->igst_amount > 0)
                <div class="flex justify-between text-gray-500"><span>IGST</span><span>₹{{ number_format($invoice->igst_amount, 2) }}</span></div>
                @endif
                <div class="flex justify-between font-semibold text-gray-900 text-base pt-1.5 border-t border-gray-200"><span>Total</span><span>₹{{ number_format($invoice->total_amount, 2) }}</span></div>
                @if($invoice->balance_amount > 0 && $invoice->balance_amount != $invoice->total_amount)
                <div class="flex justify-between text-orange-600"><span>Balance Due</span><span>₹{{ number_format($invoice->balance_amount, 2) }}</span></div>
                @endif
            </div>
        </div>
    </div>

    @if($invoice->notes)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Notes</p>
        <p class="text-sm text-gray-600">{{ $invoice->notes }}</p>
    </div>
    @endif
</div>
@endsection
