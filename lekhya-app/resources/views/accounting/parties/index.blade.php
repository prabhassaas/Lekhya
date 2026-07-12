@extends('layouts.app')
@section('title', 'Vendors & Customers')
@section('page-title', 'Vendors & Customers')

@section('content')
@php
    $labels = ['vendor' => 'Vendors', 'customer' => 'Customers', 'all' => 'All Parties'];
@endphp
<div class="py-4 space-y-6">

    {{-- Tabs + actions --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex gap-2">
            @foreach(['vendor', 'customer', 'all'] as $t)
            <a href="{{ route('accounting.parties.index', ['tab' => $t] + ($search ? ['q' => $search] : [])) }}"
               class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $tab === $t ? 'bg-navy-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                {{ $labels[$t] }}
                <span class="ml-1 text-xs {{ $tab === $t ? 'text-navy-100' : 'text-gray-400' }}">{{ $counts[$t] }}</span>
            </a>
            @endforeach
        </div>
        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('accounting.parties.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="relative">
                    <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Name, GSTIN, phone, email"
                           class="pl-8 pr-3 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:ring-2 focus:ring-navy-300 outline-none">
                </div>
            </form>
            <a href="{{ route('accounting.parties.export', ['tab' => $tab] + ($search ? ['q' => $search] : [])) }}"
               class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg whitespace-nowrap">
                <i class="fa fa-file-csv mr-1.5"></i>Export CSV
            </a>
        </div>
    </div>

    <p class="text-xs text-gray-400 -mt-3">
        <i class="fa fa-circle-info mr-1"></i>Vendors are added automatically when you scan &amp; approve a purchase bill. Outstanding shows the unpaid balance across their bills.
    </p>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Name</th>
                    <th class="text-left px-5 py-2.5">Type</th>
                    <th class="text-left px-5 py-2.5">GSTIN</th>
                    <th class="text-left px-5 py-2.5">PAN</th>
                    <th class="text-left px-5 py-2.5">Phone</th>
                    <th class="text-left px-5 py-2.5">Email</th>
                    <th class="text-left px-5 py-2.5">Location</th>
                    <th class="text-right px-5 py-2.5">Bills</th>
                    <th class="text-right px-5 py-2.5">Outstanding</th>
                    <th class="px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($parties as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <a href="{{ route('accounting.parties.show', $p) }}" class="text-navy-600 font-medium hover:underline">{{ $p->name }}</a>
                        @unless($p->is_active)<span class="ml-1 text-xs text-gray-400">(inactive)</span>@endunless
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $p->type === 'vendor' ? 'bg-amber-100 text-amber-700' : ($p->type === 'customer' ? 'bg-teal-100 text-teal-700' : 'bg-purple-100 text-purple-700') }}">
                            {{ ucfirst($p->type) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $p->gstin ?: '—' }}</td>
                    <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $p->pan ?: '—' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $p->phone ?: '—' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $p->email ?: '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ collect([$p->city, $p->state])->filter()->implode(', ') ?: '—' }}</td>
                    <td class="px-5 py-3 text-right text-gray-500">{{ $p->invoices_count }}</td>
                    <td class="px-5 py-3 text-right {{ ($balances[$p->id] ?? 0) > 0 ? 'text-orange-600 font-medium' : 'text-gray-400' }}">
                        ₹{{ number_format($balances[$p->id] ?? 0, 2) }}
                    </td>
                    <td class="px-5 py-3 text-right">
                        <form method="POST" action="{{ route('accounting.parties.destroy', $p) }}"
                              onsubmit="return confirm('Delete “{{ $p->name }}”? This cannot be undone.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-gray-300 hover:text-red-600" title="Delete">
                                <i class="fa fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-5 py-10 text-center text-gray-400">
                        No {{ $tab === 'all' ? 'parties' : $labels[$tab] }} yet.
                        @if($tab === 'vendor')
                            <a href="{{ route('ai.index') }}" class="text-blue-600 hover:underline">Scan a purchase bill →</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($parties->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $parties->links() }}</div>
        @endif
    </div>
</div>
@endsection
