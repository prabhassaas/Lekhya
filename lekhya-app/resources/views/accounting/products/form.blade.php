@extends('layouts.app')
@section('title', $product->exists ? 'Edit product' : 'Add product')
@section('page-title', $product->exists ? 'Edit product' : 'Add product')

@section('content')
<div class="py-4 max-w-3xl"
     x-data="{
        hsn: @js(old('hsn_sac_code', $product->hsn_sac_code)),
        gst: @js(old('gst_rate', $product->gst_rate)),
        hsnMsg: '',
        lookupHsn() {
            var code = (this.hsn || '').trim();
            if (code.length < 2) { this.hsnMsg = ''; return; }
            this.hsnMsg = 'Looking up…';
            fetch(@js(route('accounting.hsn.lookup')) + '?code=' + encodeURIComponent(code), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(d => {
                    if (d.rate !== null && d.rate !== undefined) {
                        this.gst = d.rate;
                        this.hsnMsg = '✓ ' + (d.description ? d.description.slice(0, 48) : 'Mapped') + ' · GST ' + d.rate + '%';
                    } else {
                        this.hsnMsg = 'No rate in master — enter GST% manually.';
                    }
                })
                .catch(() => { this.hsnMsg = ''; });
        }
     }">

    <a href="{{ route('accounting.products.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
        <i class="fa fa-arrow-left mr-1.5"></i>Back to inventory
    </a>

    <form method="POST" action="{{ $product->exists ? route('accounting.products.update', $product) : route('accounting.products.store') }}"
          class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
        @csrf
        @if($product->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Product / service name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required value="{{ old('name', $product->name) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="product" @selected(old('type', $product->type ?: 'product') === 'product')>Goods (HSN)</option>
                    <option value="service" @selected(old('type', $product->type) === 'service')>Service (SAC)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SKU / code</label>
                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" maxlength="60" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quality / grade</label>
                <input type="text" name="quality" value="{{ old('quality', $product->quality) }}" placeholder="Grade A, Premium…" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dimension</label>
                <input type="text" name="dimension" value="{{ old('dimension', $product->dimension) }}" placeholder="12 x 8 x 2 cm" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                <input type="text" name="unit" value="{{ old('unit', $product->unit ?: 'nos') }}" maxlength="20" placeholder="nos, kg, mtr, box…" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        {{-- HSN + GST auto-map --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-gray-100">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">HSN / SAC code</label>
                <input type="text" name="hsn_sac_code" x-model="hsn" @change="lookupHsn()" @blur="lookupHsn()"
                       maxlength="15" placeholder="e.g. 5208" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                <p class="text-xs mt-1" :class="hsnMsg.startsWith('✓') ? 'text-green-600' : 'text-gray-400'" x-text="hsnMsg"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">GST rate (%)</label>
                <input type="number" step="0.01" name="gst_rate" x-model="gst" placeholder="Auto-filled from HSN" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <p class="text-xs text-gray-400 mt-1">Auto-mapped from the HSN master — override if needed.</p>
            </div>
        </div>

        {{-- Pricing + stock --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-gray-100">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sale price (₹)</label>
                <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purchase price (₹)</label>
                <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2" x-data="{ track: {{ old('track_inventory', $product->track_inventory) ? 'true' : 'false' }} }">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="track_inventory" value="1" x-model="track" class="rounded border-gray-300">
                    Track stock for this item
                </label>
                <div x-show="track" x-cloak class="mt-3 max-w-xs">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Opening stock ({{ old('unit', $product->unit ?: 'nos') }})</label>
                    <input type="number" step="0.001" name="opening_stock" value="{{ old('opening_stock', $product->opening_stock ?: 0) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2 border-t border-gray-50">
            <button type="submit" class="px-5 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">
                {{ $product->exists ? 'Save changes' : 'Add product' }}
            </button>
            <a href="{{ route('accounting.products.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</div>
@endsection
