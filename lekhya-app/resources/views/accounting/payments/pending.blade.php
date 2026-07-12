@extends('layouts.app')
@section('title', 'Pending Payments')
@section('page-title', 'Pending Payments')

@section('content')
@php
    $isPayable = $direction === 'payable';
    $partyLabel = $isPayable ? 'Vendor' : 'Customer';
    $today = now()->startOfDay();
@endphp
<div class="py-4 space-y-6">

    {{-- Toggle + export --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex gap-2">
            <a href="{{ route('accounting.payments.pending', ['direction' => 'payable']) }}"
               class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $isPayable ? 'bg-navy-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                <i class="fa fa-arrow-up-from-bracket mr-1"></i>To Pay (Purchases)
            </a>
            <a href="{{ route('accounting.payments.pending', ['direction' => 'receivable']) }}"
               class="px-3 py-1.5 text-sm font-medium rounded-lg {{ !$isPayable ? 'bg-navy-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                <i class="fa fa-arrow-down-to-bracket mr-1"></i>To Receive (Sales)
            </a>
        </div>
        <a href="{{ route('accounting.payments.export', ['direction' => $direction]) }}"
           class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg">
            <i class="fa fa-file-csv mr-1.5"></i>Export CSV
        </a>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider">{{ $isPayable ? 'Total Payable' : 'Total Receivable' }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">₹{{ number_format($summary['total'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider">Overdue</p>
            <p class="text-2xl font-bold {{ $summary['overdue'] > 0 ? 'text-red-600' : 'text-gray-900' }} mt-1">₹{{ number_format($summary['overdue'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider">Open {{ $isPayable ? 'Bills' : 'Invoices' }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['count'] }}</p>
        </div>
    </div>

    <p class="text-xs text-gray-400 -mt-2">
        <i class="fa fa-circle-info mr-1"></i>Shows every {{ $isPayable ? 'purchase bill you owe' : 'sales invoice owed to you' }} with an unpaid balance — including drafts you scanned but haven't posted yet. Overdue = past its due date.
    </p>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">{{ $partyLabel }}</th>
                    <th class="text-left px-5 py-2.5">Bill / Ref</th>
                    <th class="text-left px-5 py-2.5">Invoice #</th>
                    <th class="text-left px-5 py-2.5">Date</th>
                    <th class="text-left px-5 py-2.5">Due</th>
                    <th class="text-right px-5 py-2.5">Total</th>
                    <th class="text-right px-5 py-2.5">Balance</th>
                    <th class="text-right px-5 py-2.5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($invoices as $inv)
                @php $overdue = $inv->due_date && $inv->due_date->lt($today); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <a href="{{ route('accounting.parties.show', $inv->party_id) }}" class="text-navy-600 font-medium hover:underline">{{ $inv->party->name ?? '—' }}</a>
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $inv->reference_number ?: '—' }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('accounting.invoices.show', $inv) }}" class="text-gray-700 hover:underline">{{ $inv->invoice_number }}</a>
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $inv->invoice_date?->format('d M Y') }}</td>
                    <td class="px-5 py-3">
                        @if($inv->due_date)
                            <span class="{{ $overdue ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                {{ $inv->due_date->format('d M Y') }}
                                @if($overdue)<span class="ml-1 text-xs">({{ $today->diffInDays($inv->due_date) }}d)</span>@endif
                            </span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right text-gray-900">₹{{ number_format($inv->total_amount, 2) }}</td>
                    <td class="px-5 py-3 text-right font-semibold {{ $overdue ? 'text-red-600' : 'text-orange-600' }}">₹{{ number_format($inv->balance_amount, 2) }}</td>
                    <td class="px-5 py-3 text-right">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $inv->status === 'posted' ? 'bg-green-100 text-green-700' :
                               ($inv->status === 'draft' ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700') }}">
                            {{ ucfirst(str_replace('_', ' ', $inv->status)) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-gray-400">
                        <i class="fa fa-check-circle text-green-400 text-2xl mb-2 block"></i>
                        Nothing pending — all {{ $isPayable ? 'bills are settled' : 'invoices are collected' }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($invoices->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>
@endsection
