@extends('layouts.app')
@section('title', 'AR Aging')
@section('page-title', 'Accounts Receivable Aging')

@section('content')
@php
    $buckets = ['0-30' => 'blue', '31-60' => 'yellow', '61-90' => 'orange', '90+' => 'red'];
    $byBucket = collect($invoices)->groupBy('bucket');
    $totalOutstanding = collect($invoices)->sum('balance_amount');
@endphp
<div class="py-4 space-y-6">
    <div class="flex justify-end">
        <x-report-share type="ar-aging" />
    </div>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Outstanding</p>
            <p class="text-xl font-bold text-gray-900 mt-1">₹{{ number_format($totalOutstanding, 0) }}</p>
        </div>
        @foreach($buckets as $bucket => $color)
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">{{ $bucket }} days</p>
            <p class="text-xl font-bold text-{{ $color }}-600 mt-1">₹{{ number_format(($byBucket[$bucket] ?? collect())->sum('balance_amount'), 0) }}</p>
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Invoice #</th>
                    <th class="text-left px-5 py-2.5">Customer</th>
                    <th class="text-left px-5 py-2.5">Date</th>
                    <th class="text-right px-5 py-2.5">Days</th>
                    <th class="text-right px-5 py-2.5">Balance</th>
                    <th class="text-right px-5 py-2.5">Bucket</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($invoices as $inv)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3"><a href="{{ route('accounting.invoices.show', $inv['id']) }}" class="text-navy-600 font-medium hover:underline">{{ $inv['invoice_number'] }}</a></td>
                    <td class="px-5 py-3 text-gray-700">{{ $inv['party']['name'] ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ \Carbon\Carbon::parse($inv['invoice_date'])->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-right text-gray-500">{{ $inv['days_outstanding'] }}</td>
                    <td class="px-5 py-3 text-right font-medium text-gray-900">₹{{ number_format($inv['balance_amount'], 2) }}</td>
                    <td class="px-5 py-3 text-right">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-{{ $buckets[$inv['bucket']] }}-100 text-{{ $buckets[$inv['bucket']] }}-700">{{ $inv['bucket'] }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No outstanding receivables.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
