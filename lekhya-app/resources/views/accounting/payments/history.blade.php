@extends('layouts.app')
@section('title', 'Receipts & Payments')
@section('page-title', 'Receipts & Payments')

@section('content')
<div class="py-4 space-y-5">
    <div class="flex items-center justify-between">
        <x-filter-tabs :tabs="['' => ['label' => 'All', 'count' => $counts['receipt'] + $counts['payment']], 'receipt' => ['label' => 'Receipts', 'count' => $counts['receipt']], 'payment' => ['label' => 'Payments', 'count' => $counts['payment']]]" :active="$type ?? ''" param="type" />
        <div class="flex gap-2">
            <a href="{{ route('accounting.payments.record', ['type' => 'receipt']) }}" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg"><i class="fa fa-arrow-down-long mr-1.5"></i>Record Receipt</a>
            <a href="{{ route('accounting.payments.record', ['type' => 'payment']) }}" class="px-4 py-2 border border-navy-600 text-navy-700 text-sm font-medium rounded-lg hover:bg-navy-50"><i class="fa fa-arrow-up-long mr-1.5"></i>Record Payment</a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5"><x-sort-header label="Voucher #" column="reference_number" /></th>
                    <th class="text-left px-5 py-2.5"><x-sort-header label="Date" column="date" /></th>
                    <th class="text-left px-5 py-2.5"><x-sort-header label="Type" column="type" /></th>
                    <th class="text-left px-5 py-2.5">Party</th>
                    <th class="text-left px-5 py-2.5">Into / From</th>
                    <th class="text-left px-5 py-2.5">Mode / Ref</th>
                    <th class="text-right px-5 py-2.5"><x-sort-header label="Amount" column="amount" align="right" /></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($payments as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3"><a href="{{ route('accounting.payments.show', $p) }}" class="text-navy-600 font-medium hover:underline">{{ $p->reference_number }}</a></td>
                    <td class="px-5 py-3 text-gray-500">{{ $p->date->format('d M Y') }}</td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $p->type === 'receipt' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">{{ $p->label() }}</span>
                    </td>
                    <td class="px-5 py-3 text-gray-800">{{ $p->party->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $p->ledgerAccount->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ ucwords(str_replace('_', ' ', (string) $p->payment_mode)) }}@if($p->transaction_reference) · {{ $p->transaction_reference }}@endif</td>
                    <td class="px-5 py-3 text-right font-semibold {{ $p->type === 'receipt' ? 'text-green-700' : 'text-orange-700' }}">₹{{ number_format((float) $p->amount, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">No receipts or payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($payments->hasPages())<div class="p-4 border-t border-gray-100">{{ $payments->links() }}</div>@endif
    </div>
</div>
@endsection
