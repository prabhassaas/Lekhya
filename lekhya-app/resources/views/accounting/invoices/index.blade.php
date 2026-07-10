@extends('layouts.app')
@section('title', $type === 'sales' ? 'Sales Invoices' : 'Purchase Invoices')
@section('page-title', $type === 'sales' ? 'Sales Invoices' : 'Purchase Invoices')

@section('content')
<div class="py-4 space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex gap-2">
            <a href="{{ route('accounting.invoices.index', ['type' => 'sales']) }}"
               class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $type === 'sales' ? 'bg-navy-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Sales</a>
            <a href="{{ route('accounting.invoices.index', ['type' => 'purchase']) }}"
               class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $type === 'purchase' ? 'bg-navy-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Purchase</a>
        </div>
        <a href="{{ route('accounting.invoices.create', ['type' => $type]) }}" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
            <i class="fa fa-plus mr-1.5"></i>New {{ $type === 'sales' ? 'Sales' : 'Purchase' }} Invoice
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Invoice #</th>
                    <th class="text-left px-5 py-2.5">Party</th>
                    <th class="text-left px-5 py-2.5">Date</th>
                    <th class="text-right px-5 py-2.5">Total</th>
                    <th class="text-right px-5 py-2.5">Balance</th>
                    <th class="text-right px-5 py-2.5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($invoices as $inv)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3"><a href="{{ route('accounting.invoices.show', $inv) }}" class="text-navy-600 font-medium hover:underline">{{ $inv->invoice_number }}</a></td>
                    <td class="px-5 py-3 text-gray-700">{{ $inv->party->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $inv->invoice_date->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-right font-medium text-gray-900">₹{{ number_format($inv->total_amount, 2) }}</td>
                    <td class="px-5 py-3 text-right {{ $inv->balance_amount > 0 ? 'text-orange-600' : 'text-gray-400' }}">₹{{ number_format($inv->balance_amount, 2) }}</td>
                    <td class="px-5 py-3 text-right">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $inv->status === 'posted' ? 'bg-green-100 text-green-700' :
                               ($inv->status === 'draft' ? 'bg-gray-100 text-gray-600' :
                               ($inv->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700')) }}">
                            {{ ucfirst($inv->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-gray-400">
                        No {{ $type }} invoices yet.
                        <a href="{{ route('accounting.invoices.create', ['type' => $type]) }}" class="text-blue-600 hover:underline">Create one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($invoices->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>
@endsection
