@extends('layouts.app')
@section('title', 'Search')
@section('page-title', 'Search')

@section('content')
<div class="py-4 space-y-6 max-w-4xl">

    <form method="GET" action="{{ route('search') }}" class="relative">
        <i class="fa fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" name="q" value="{{ $q }}" autofocus
               placeholder="Search invoice #, GSTIN, client name, phone number…"
               class="w-full pl-11 pr-4 py-3 text-sm bg-white border border-gray-200 rounded-xl shadow-sm focus:border-navy-400 focus:ring-1 focus:ring-navy-200 outline-none">
    </form>

    @if(strlen(trim($q)) < 2)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-10 text-center text-gray-400">
            <i class="fa fa-magnifying-glass text-3xl mb-3"></i>
            <p class="text-sm">Type at least 2 characters to search across invoices, vendors and customers.</p>
        </div>
    @else
        <p class="text-sm text-gray-500">
            {{ $invoices->count() + $parties->count() }} result(s) for
            <span class="font-medium text-gray-800">“{{ $q }}”</span>
        </p>

        {{-- Invoices --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm"><i class="fa fa-file-invoice text-navy-500 mr-2"></i>Invoices &amp; Bills</h3>
                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $invoices->count() }}</span>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($invoices as $inv)
                <a href="{{ route('accounting.invoices.show', $inv) }}" class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $inv->invoice_number }}
                            <span class="ml-1 text-xs font-normal px-1.5 py-0.5 rounded-full {{ $inv->type === 'sales' ? 'bg-teal-50 text-teal-700' : 'bg-amber-50 text-amber-700' }}">{{ ucfirst($inv->type) }}</span>
                        </p>
                        <p class="text-xs text-gray-400 truncate">{{ $inv->party?->name ?? 'Walk-in' }} · {{ $inv->invoice_date?->format('d M Y') }}</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0
                        {{ $inv->status === 'posted' ? 'bg-green-100 text-green-700' :
                           ($inv->status === 'draft' ? 'bg-gray-100 text-gray-600' :
                           ($inv->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700')) }}">
                        {{ ucfirst(str_replace('_', ' ', $inv->status)) }}
                    </span>
                    <span class="text-sm font-semibold text-gray-900 shrink-0 w-28 text-right">₹{{ number_format($inv->total_amount, 2) }}</span>
                </a>
                @empty
                <div class="px-5 py-8 text-center text-gray-400 text-sm">No matching invoices.</div>
                @endforelse
            </div>
        </div>

        {{-- Parties --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm"><i class="fa fa-address-book text-navy-500 mr-2"></i>Vendors &amp; Customers</h3>
                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $parties->count() }}</span>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($parties as $p)
                <a href="{{ route('accounting.parties.show', $p) }}" class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50">
                    <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0
                        {{ $p->type === 'customer' ? 'bg-teal-50 text-teal-600' : ($p->type === 'vendor' ? 'bg-amber-50 text-amber-600' : 'bg-purple-50 text-purple-600') }}">
                        <i class="fa fa-user text-sm"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $p->name }}</p>
                        <p class="text-xs text-gray-400 truncate">
                            {{ collect([$p->gstin, $p->phone, $p->email])->filter()->implode(' · ') ?: '—' }}
                        </p>
                    </div>
                    <span class="text-[11px] uppercase tracking-wide text-gray-400 shrink-0">{{ $p->type }}</span>
                </a>
                @empty
                <div class="px-5 py-8 text-center text-gray-400 text-sm">No matching vendors or customers.</div>
                @endforelse
            </div>
        </div>
    @endif
</div>
@endsection
