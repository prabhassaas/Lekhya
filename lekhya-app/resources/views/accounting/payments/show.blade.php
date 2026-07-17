@extends('layouts.app')
@section('title', $payment->reference_number)
@section('page-title', $payment->reference_number)

@section('content')
<div class="py-4 space-y-6 max-w-4xl">
    <div class="flex items-center gap-3">
        <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $payment->type === 'receipt' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">{{ $payment->label() }}</span>
        <span class="text-gray-500 text-sm">{{ $payment->date->format('d M Y') }}</span>
        <a href="{{ route('accounting.payments.history', ['type' => $payment->type]) }}" class="ml-auto text-sm text-navy-600 hover:underline">← Back to history</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 grid grid-cols-2 gap-6">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">{{ $payment->type === 'receipt' ? 'Received from' : 'Paid to' }}</p>
            <p class="font-semibold text-gray-900">{{ $payment->party->name ?? '—' }}</p>
            <p class="text-sm text-gray-500 mt-2">{{ $payment->type === 'receipt' ? 'Into' : 'From' }}: {{ $payment->ledgerAccount->name ?? '—' }}</p>
            <p class="text-sm text-gray-500">Mode: {{ ucwords(str_replace('_', ' ', (string) $payment->payment_mode)) }}@if($payment->transaction_reference) · Ref {{ $payment->transaction_reference }}@endif</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Amount settled</p>
            <p class="text-2xl font-bold text-gray-900">₹{{ number_format((float) $payment->amount, 2) }}</p>
            @if((float) $payment->tds_amount > 0)
            <p class="text-sm text-indigo-600 mt-1">incl. TDS ₹{{ number_format((float) $payment->tds_amount, 2) }} · cash ₹{{ number_format((float) $payment->amount - (float) $payment->tds_amount, 2) }}</p>
            @endif
        </div>
    </div>

    {{-- Bills settled --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 font-medium text-gray-800">Bills settled</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr><th class="text-left px-5 py-2.5">Invoice #</th><th class="text-left px-5 py-2.5">Current status</th><th class="text-right px-5 py-2.5">Applied</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($payment->allocations as $a)
                <tr>
                    <td class="px-5 py-3"><a href="{{ route('accounting.invoices.show', $a->invoice_id) }}" class="text-navy-600 hover:underline">{{ $a->invoice->invoice_number ?? ('#' . $a->invoice_id) }}</a></td>
                    <td class="px-5 py-3"><span class="text-xs px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-600 capitalize">{{ str_replace('_', ' ', $a->invoice->status ?? '') }}</span></td>
                    <td class="px-5 py-3 text-right font-medium text-gray-900">₹{{ number_format((float) $a->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Posted journal --}}
    @if($payment->journal)
    <div class="bg-white rounded-xl border border-navy-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 bg-navy-50 border-b border-navy-100">
            <p class="text-sm font-semibold text-navy-800"><i class="fa fa-book mr-1"></i>{{ $payment->journal->voucher_number }} <span class="text-gray-400 font-normal">Double-entry</span></p>
            <a href="{{ route('accounting.journals.show', $payment->journal) }}" class="text-xs text-navy-600 hover:underline">Open voucher →</a>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider"><tr><th class="text-left px-5 py-2.5">Account</th><th class="text-right px-5 py-2.5">Debit</th><th class="text-right px-5 py-2.5">Credit</th></tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($payment->journal->lines as $jl)
                <tr>
                    <td class="px-5 py-2.5 text-gray-900"><span class="font-mono text-xs text-gray-400 mr-1.5">{{ $jl->account->code ?? '' }}</span>{{ $jl->account->name ?? '—' }}</td>
                    <td class="px-5 py-2.5 text-right {{ $jl->debit > 0 ? 'text-gray-900 font-medium' : 'text-gray-300' }}">{{ $jl->debit > 0 ? '₹' . number_format($jl->debit, 2) : '—' }}</td>
                    <td class="px-5 py-2.5 text-right {{ $jl->credit > 0 ? 'text-gray-900 font-medium' : 'text-gray-300' }}">{{ $jl->credit > 0 ? '₹' . number_format($jl->credit, 2) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
