@extends('layouts.app')
@section('title', $type === 'sales' ? 'Sales Invoices' : 'Purchase Invoices')
@section('page-title', $type === 'sales' ? 'Sales Invoices' : 'Purchase Invoices')

@section('content')
<div class="py-4 space-y-6" x-data="{ scanOpen: false, newOpen: false }">
    <div class="flex items-center justify-between">
        <div class="flex gap-2">
            @php $isCancelledView = ($view ?? null) === 'cancelled'; @endphp
            <a href="{{ route('accounting.invoices.index', ['type' => 'sales']) }}"
               class="px-3 py-1.5 text-sm font-medium rounded-lg {{ !$isCancelledView && $type === 'sales' ? 'bg-navy-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Sales</a>
            <a href="{{ route('accounting.invoices.index', ['type' => 'purchase']) }}"
               class="px-3 py-1.5 text-sm font-medium rounded-lg {{ !$isCancelledView && $type === 'purchase' ? 'bg-navy-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Purchase</a>
            <a href="{{ route('accounting.invoices.index', ['view' => 'cancelled']) }}"
               class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $isCancelledView ? 'bg-red-600 text-white' : 'text-gray-500 hover:bg-gray-100' }}">
                Cancelled / Reversed @if(($cancelledCount ?? 0) > 0)<span class="ml-1 text-xs opacity-80">({{ $cancelledCount }})</span>@endif
            </a>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="scanOpen = true" class="px-4 py-2 border border-amber-300 text-amber-700 hover:bg-amber-50 text-sm font-medium rounded-lg">
                <i class="fa fa-wand-magic-sparkles mr-1.5"></i>Scan Invoice (AI)
            </button>
            @if($type === 'sales' && !$isCancelledView)
            <div class="relative" @click.outside="newOpen = false">
                <button type="button" @click="newOpen = !newOpen" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
                    <i class="fa fa-plus mr-1.5"></i>New <i class="fa fa-chevron-down text-[10px] ml-1"></i>
                </button>
                <div x-show="newOpen" x-cloak x-transition class="absolute right-0 mt-2 w-52 bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden z-40">
                    @foreach(\App\Models\Invoice::DOCUMENT_TYPES as $__v => $__l)
                    <a href="{{ route('accounting.invoices.create', ['type' => 'sales', 'doc' => $__v]) }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">{{ $__l }}</a>
                    @endforeach
                </div>
            </div>
            @else
            <a href="{{ route('accounting.invoices.create', ['type' => $type]) }}" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
                <i class="fa fa-plus mr-1.5"></i>New {{ $type === 'sales' ? 'Sales' : 'Purchase' }} Invoice
            </a>
            @endif
        </div>
    </div>

    {{-- Sales document-type sub-filter --}}
    @if($type === 'sales' && !$isCancelledView && ($docCounts ?? collect())->isNotEmpty())
    @php $__docTabs = ['' => ['label' => 'All documents', 'count' => $docCounts->sum()]];
        foreach(\App\Models\Invoice::DOCUMENT_TYPES as $__k => $__lbl) { if(($docCounts[$__k] ?? 0) > 0) $__docTabs[$__k] = ['label' => $__lbl, 'count' => $docCounts[$__k]]; }
    @endphp
    <x-filter-tabs :tabs="$__docTabs" :active="$doc ?? ''" param="doc" />
    @endif

    {{-- Scan / camera modal — reads a PDF or photo of an invoice and drops you on the AI review screen --}}
    <div x-show="scanOpen" x-cloak class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 p-4" @click.self="scanOpen = false">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-semibold text-gray-900">Scan an invoice</h3>
                <button type="button" @click="scanOpen = false" class="text-gray-400 hover:text-gray-600"><i class="fa fa-xmark"></i></button>
            </div>
            <p class="text-sm text-gray-500 mb-5">Upload a PDF/image or snap a photo. AI reads the fields; you review and approve before it posts.</p>

            <div class="grid grid-cols-2 gap-3">
                {{-- Upload a file (PDF/JPG/PNG) --}}
                <form action="{{ route('ai.extract') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-300 hover:border-navy-400 rounded-xl p-6 cursor-pointer text-center">
                        <i class="fa fa-cloud-arrow-up text-2xl text-navy-500"></i>
                        <span class="text-sm font-medium text-gray-700">Upload file</span>
                        <span class="text-xs text-gray-400">PDF, JPG, PNG</span>
                        <input type="file" name="file" class="hidden" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/*" onchange="this.form.submit()">
                    </label>
                </form>

                {{-- Take a photo (mobile back camera) --}}
                <form action="{{ route('ai.extract') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-300 hover:border-navy-400 rounded-xl p-6 cursor-pointer text-center">
                        <i class="fa fa-camera text-2xl text-navy-500"></i>
                        <span class="text-sm font-medium text-gray-700">Take photo</span>
                        <span class="text-xs text-gray-400">Use camera</span>
                        <input type="file" name="file" class="hidden" accept="image/*" capture="environment" onchange="this.form.submit()">
                    </label>
                </form>
            </div>
            <p class="text-xs text-gray-400 mt-4"><i class="fa fa-shield-halved mr-1"></i>Nothing posts automatically — you approve every entry.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5"><x-sort-header label="Invoice #" column="invoice_number" /></th>
                    <th class="text-left px-5 py-2.5"><x-sort-header :label="($view ?? null) === 'cancelled' ? 'Ref / Bill #' : ($type === 'purchase' ? 'Vendor Bill #' : 'Ref #')" column="reference_number" /></th>
                    <th class="text-left px-5 py-2.5"><x-sort-header label="Party" column="party" /></th>
                    <th class="text-left px-5 py-2.5"><x-sort-header label="Type" column="type" /></th>
                    <th class="text-left px-5 py-2.5"><x-sort-header label="Date" column="invoice_date" /></th>
                    <th class="text-right px-5 py-2.5"><x-sort-header label="Total" column="total_amount" align="right" /></th>
                    <th class="text-right px-5 py-2.5"><x-sort-header label="Balance" column="balance_amount" align="right" /></th>
                    <th class="text-right px-5 py-2.5"><x-sort-header label="Status" column="status" align="right" /></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($invoices as $inv)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <a href="{{ route('accounting.invoices.show', $inv) }}" class="text-navy-600 font-medium hover:underline">{{ $inv->invoice_number }}</a>
                        @if(($inv->document_type ?? 'tax_invoice') !== 'tax_invoice')
                        <span class="ml-1.5 text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600 font-medium align-middle">{{ \App\Models\Invoice::DOCUMENT_TYPES[$inv->document_type] ?? ucfirst(str_replace('_',' ',$inv->document_type)) }}</span>
                        @endif
                        @if($inv->converted_from_id)
                        <span class="ml-1 text-[10px] text-gray-400" title="Converted from another document"><i class="fa fa-arrow-turn-up fa-rotate-90"></i></span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500 font-mono text-xs">{{ $inv->reference_number ?: '—' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $inv->party->name ?? '—' }}</td>
                    <td class="px-5 py-3">
                        @if($inv->party)
                        @php $pc = $inv->party->classificationColor(); @endphp
                        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium bg-{{ $pc }}-100 text-{{ $pc }}-700">{{ $inv->party->classificationLabel() }}</span>
                        @else — @endif
                    </td>
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
                    <td colspan="8" class="px-5 py-10 text-center text-gray-400">
                        @if(($view ?? null) === 'cancelled')
                        No cancelled or reversed bills.
                        @else
                        No {{ $type }} invoices yet.
                        <a href="{{ route('accounting.invoices.create', ['type' => $type]) }}" class="text-blue-600 hover:underline">Create one →</a>
                        @endif
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
