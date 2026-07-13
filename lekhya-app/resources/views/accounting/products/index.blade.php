@extends('layouts.app')
@section('title', 'Inventory')
@section('page-title', 'Inventory / Products')

@section('content')
<div class="py-4 space-y-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="relative w-full sm:w-80">
            <i class="fa fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="q" value="{{ $search }}" placeholder="Search name, SKU, HSN, quality…"
                   class="w-full pl-9 pr-3 py-2 text-sm bg-white border border-gray-200 rounded-lg focus:border-navy-400 focus:ring-1 focus:ring-navy-200 outline-none">
        </form>
        <a href="{{ route('accounting.products.create') }}" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
            <i class="fa fa-plus mr-1.5"></i>Add product
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Product</th>
                    <th class="text-left px-5 py-2.5">Quality</th>
                    <th class="text-left px-5 py-2.5">Dimension</th>
                    <th class="text-left px-5 py-2.5">HSN/SAC</th>
                    <th class="text-right px-5 py-2.5">GST %</th>
                    <th class="text-right px-5 py-2.5">Sale price</th>
                    <th class="text-right px-5 py-2.5">Stock</th>
                    <th class="px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($products as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-900">{{ $p->name }}</p>
                        <p class="text-xs text-gray-400">
                            {{ ucfirst($p->type) }}@if($p->sku) · {{ $p->sku }}@endif · per {{ $p->unit }}
                        </p>
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $p->quality ?: '—' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $p->dimension ?: '—' }}</td>
                    <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $p->hsn_sac_code ?: '—' }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ $p->gst_rate !== null ? rtrim(rtrim(number_format($p->gst_rate, 2), '0'), '.') . '%' : '—' }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ $p->sale_price !== null ? '₹' . number_format($p->sale_price, 2) : '—' }}</td>
                    <td class="px-5 py-3 text-right {{ $p->track_inventory ? 'text-gray-700' : 'text-gray-300' }}">
                        {{ $p->track_inventory ? rtrim(rtrim(number_format($p->current_stock, 3), '0'), '.') : '—' }}
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('accounting.products.edit', $p) }}" class="text-navy-600 hover:text-navy-700 text-sm"><i class="fa fa-pen"></i></a>
                        <form method="POST" action="{{ route('accounting.products.destroy', $p) }}" class="inline ml-2"
                              onsubmit="return confirm('Remove “{{ $p->name }}”?');">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-600 text-sm"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-5 py-12 text-center text-gray-400">
                    @if($count === 0)
                        No products yet. <a href="{{ route('accounting.products.create') }}" class="text-navy-600 hover:underline">Add your first product</a> — its HSN &amp; GST rate will auto-fill on invoices.
                    @else
                        No products match “{{ $search }}”.
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($products->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $products->links() }}</div>
        @endif
    </div>
</div>
@endsection
