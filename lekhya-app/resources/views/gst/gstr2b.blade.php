@extends('layouts.app')
@section('title', 'GSTR-2B Reconciliation')
@section('page-title', 'GSTR-2B — ITC Reconciliation')

@section('content')
@php
    $money = fn($v) => '₹' . number_format((float) $v, 2);
    $matched   = $reconciliations->where('status', 'matched')->count();
    $mismatch  = $reconciliations->whereIn('status', ['mismatch', 'partial'])->count();
    $missing   = $reconciliations->where('status', 'missing')->count();
    $importRows = ($latestImport && is_array($latestImport->data)) ? count($latestImport->data) : null;
    // Reconciliations keyed by book invoice id, to annotate the books table
    $recByInvoice = $reconciliations->keyBy('invoice_id');
@endphp
<div class="py-4 space-y-6">

    {{-- Header / period --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-gray-500">Period</label>
            <input type="month" value="{{ substr($period, 2, 4) . '-' . substr($period, 0, 2) }}"
                   onchange="this.form.period.value = this.value.slice(5,7) + this.value.slice(0,4); this.form.submit()"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
            <input type="hidden" name="period" value="{{ $period }}">
        </form>

        {{-- Import 2B JSON --}}
        <form method="POST" action="{{ route('gst.gstr2b.import') }}" enctype="multipart/form-data" class="flex items-center gap-2">
            @csrf
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="file" name="file" accept=".json,application/json" required
                   class="text-xs file:mr-2 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-navy-50 file:text-navy-700 file:text-xs file:font-medium hover:file:bg-navy-100">
            <button class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg whitespace-nowrap">
                <i class="fa fa-upload mr-1.5"></i>Import 2B
            </button>
        </form>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">ITC in your books</p>
            <p class="text-xl font-bold text-gray-900 mt-1">{{ $money($bookItc['tax']) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $bookItc['count'] }} purchase bills</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">GSTN 2B data</p>
            @if($latestImport)
                <p class="text-xl font-bold text-gray-900 mt-1">{{ $importRows !== null ? $importRows : 'Imported' }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $importRows !== null ? 'records' : '' }} · {{ $latestImport->imported_at?->format('d M, H:i') }}</p>
            @else
                <p class="text-xl font-bold text-gray-300 mt-1">—</p>
                <p class="text-xs text-gray-400 mt-0.5">Not imported yet</p>
            @endif
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Matched</p>
            <p class="text-xl font-bold text-green-600 mt-1">{{ $matched }}</p>
            <p class="text-xs text-gray-400 mt-0.5">fully reconciled</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Needs attention</p>
            <p class="text-xl font-bold text-orange-600 mt-1">{{ $mismatch + $missing }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $mismatch }} mismatch · {{ $missing }} missing</p>
        </div>
    </div>

    {{-- Reconciliation results (only if 2B has been reconciled) --}}
    @if($reconciliations->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-900 text-sm">Reconciliation results</h3></div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Supplier GSTIN</th>
                    <th class="text-left px-5 py-2.5">Supplier invoice</th>
                    <th class="text-left px-5 py-2.5">Date</th>
                    <th class="text-right px-5 py-2.5">Value</th>
                    <th class="text-right px-5 py-2.5">Tax (I/C/S)</th>
                    <th class="text-center px-5 py-2.5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 text-gray-700">
                @foreach($reconciliations as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-xs">{{ $r->gstin_supplier ?: '—' }}</td>
                    <td class="px-5 py-3">{{ $r->supplier_invoice_number ?: '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $r->invoice_date?->format('d M Y') ?: '—' }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($r->invoice_value) }}</td>
                    <td class="px-5 py-3 text-right text-xs text-gray-500">{{ number_format($r->igst, 0) }}/{{ number_format($r->cgst, 0) }}/{{ number_format($r->sgst, 0) }}</td>
                    <td class="px-5 py-3 text-center">
                        @php
                            $badge = match($r->status) {
                                'matched'  => ['bg-green-100 text-green-700', 'Matched'],
                                'mismatch' => ['bg-orange-100 text-orange-700', 'Mismatch'],
                                'partial'  => ['bg-orange-100 text-orange-700', 'Partial'],
                                'missing'  => ['bg-red-100 text-red-700', 'Missing in books'],
                                default    => ['bg-gray-100 text-gray-600', ucfirst((string) $r->status)],
                            };
                        @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $badge[0] }}">{{ $badge[1] }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @endif

    {{-- Purchases as per your books --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 text-sm">Purchases as per your books</h3>
            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $purchases->count() }}</span>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Bill #</th>
                    <th class="text-left px-5 py-2.5">Supplier</th>
                    <th class="text-left px-5 py-2.5">GSTIN</th>
                    <th class="text-right px-5 py-2.5">Taxable</th>
                    <th class="text-right px-5 py-2.5">ITC (tax)</th>
                    <th class="text-center px-5 py-2.5">2B status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 text-gray-700">
                @forelse($purchases as $p)
                @php $rec = $recByInvoice->get($p->id); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3"><a href="{{ route('accounting.invoices.show', $p) }}" class="text-navy-600 font-medium hover:underline">{{ $p->invoice_number }}</a></td>
                    <td class="px-5 py-3">{{ $p->party?->name ?? '—' }}</td>
                    <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $p->party?->gstin ?: '—' }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($p->taxable_amount) }}</td>
                    <td class="px-5 py-3 text-right">{{ $money($p->cgst_amount + $p->sgst_amount + $p->igst_amount) }}</td>
                    <td class="px-5 py-3 text-center">
                        @if(!$latestImport)
                            <span class="text-xs text-gray-300">—</span>
                        @elseif($rec && $rec->status === 'matched')
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700">Matched</span>
                        @elseif($rec)
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-orange-100 text-orange-700">{{ ucfirst((string) $rec->status) }}</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-500">Not in 2B</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No posted purchase bills for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 flex items-start gap-2">
        <i class="fa fa-circle-info mt-0.5"></i>
        <span>Download the GSTR-2B JSON from the GST portal for this period and import it above. Lekhya matches each supplier bill against your posted purchases so you only claim ITC that actually appears in 2B.</span>
    </p>
</div>
@endsection
