@extends('layouts.app')
@section('title', $party->name)
@section('page-title', $party->name)

@section('content')
<div class="py-4 space-y-6">

    <a href="{{ route('accounting.parties.index', ['tab' => $party->type === 'customer' ? 'customer' : 'vendor']) }}"
       class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
        <i class="fa fa-arrow-left mr-1.5"></i>Back to list
    </a>

    {{-- Header card --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-gray-900">{{ $party->name }}</h2>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $party->type === 'vendor' ? 'bg-amber-100 text-amber-700' : ($party->type === 'customer' ? 'bg-teal-100 text-teal-700' : 'bg-purple-100 text-purple-700') }}">
                        {{ ucfirst($party->type) }}
                    </span>
                </div>
                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-10 gap-y-2 text-sm">
                    <div class="flex gap-2"><dt class="text-gray-400 w-20">GSTIN</dt><dd class="font-mono text-gray-700">{{ $party->gstin ?: '—' }}</dd></div>
                    <div class="flex gap-2"><dt class="text-gray-400 w-20">PAN</dt><dd class="font-mono text-gray-700">{{ $party->pan ?: '—' }}</dd></div>
                    <div class="flex gap-2"><dt class="text-gray-400 w-20">Phone</dt><dd class="text-gray-700">{{ $party->phone ?: '—' }}</dd></div>
                    <div class="flex gap-2"><dt class="text-gray-400 w-20">Email</dt><dd class="text-gray-700">{{ $party->email ?: '—' }}</dd></div>
                    <div class="flex gap-2"><dt class="text-gray-400 w-20">Address</dt><dd class="text-gray-700">{{ collect([$party->address, $party->city, $party->state, $party->pincode])->filter()->implode(', ') ?: '—' }}</dd></div>
                    <div class="flex gap-2"><dt class="text-gray-400 w-20">State code</dt><dd class="text-gray-700">{{ $party->state_code ?: '—' }}</dd></div>
                </dl>
            </div>
            <div class="flex gap-3">
                <div class="text-right">
                    <p class="text-xs text-gray-400 uppercase tracking-wider">Outstanding</p>
                    <p class="text-2xl font-bold {{ $outstanding > 0 ? 'text-orange-600' : 'text-gray-900' }}">₹{{ number_format($outstanding, 2) }}</p>
                </div>
                <div class="text-right pl-6 border-l border-gray-100">
                    <p class="text-xs text-gray-400 uppercase tracking-wider">Total billed</p>
                    <p class="text-2xl font-bold text-gray-900">₹{{ number_format($billed, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Their invoices --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-800 text-sm">Invoices &amp; Bills</h3></div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Invoice #</th>
                    <th class="text-left px-5 py-2.5">Type</th>
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
                    <td class="px-5 py-3 text-gray-500">{{ ucfirst($inv->type) }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $inv->invoice_date?->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-right font-medium text-gray-900">₹{{ number_format($inv->total_amount, 2) }}</td>
                    <td class="px-5 py-3 text-right {{ $inv->balance_amount > 0 ? 'text-orange-600' : 'text-gray-400' }}">₹{{ number_format($inv->balance_amount, 2) }}</td>
                    <td class="px-5 py-3 text-right">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $inv->status === 'posted' ? 'bg-green-100 text-green-700' :
                               ($inv->status === 'draft' ? 'bg-gray-100 text-gray-600' :
                               ($inv->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700')) }}">
                            {{ ucfirst(str_replace('_', ' ', $inv->status)) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No invoices for this party yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($invoices->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>
@endsection
