@extends('layouts.app')
@section('title', 'Bank Payment File')
@section('page-title', 'Bank Payment File')

@section('content')
<div class="py-4 space-y-6 max-w-5xl">

    <div class="flex items-center justify-between">
        <a href="{{ route('accounting.payments.pending') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <i class="fa fa-arrow-left mr-1.5"></i>Back to pending payments
        </a>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Bills ready to pay</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $readyCount }}</p>
            <p class="text-xs text-gray-400 mt-0.5">with vendor bank details</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-navy-100 shadow-sm ring-1 ring-navy-100">
            <p class="text-xs text-navy-500 font-medium uppercase tracking-wider">Total in file</p>
            <p class="text-2xl font-bold text-navy-700 mt-1">₹{{ number_format($payTotal, 2) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">one row per bill</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Missing bank details</p>
            <p class="text-2xl font-bold {{ $missing->count() ? 'text-orange-600' : 'text-gray-900' }} mt-1">{{ $missing->count() }}</p>
            <p class="text-xs text-gray-400 mt-0.5">excluded from file</p>
        </div>
    </div>

    <p class="text-sm text-gray-600">
        Pick your bank to download an <strong>invoice-wise</strong> payment file in that bank's bulk-upload format, then upload it on your corporate net-banking portal.
        Payments ≥ ₹{{ number_format($rtgsFloor) }} are marked <strong>RTGS</strong>, the rest <strong>NEFT</strong>.
    </p>

    {{-- Bank grid --}}
    @if($readyCount === 0)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-sm text-amber-800">
            <i class="fa fa-triangle-exclamation mr-1.5"></i>
            No payable bills have vendor bank details yet. Add bank details on a vendor (or scan a bill that prints them) to build a payment file.
        </div>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($formats as $f)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
            <div class="h-1.5" style="background-color: {{ $f['brand'] }}"></div>
            <div class="p-4 flex flex-col flex-1">
                <div class="flex items-center gap-2.5 mb-2">
                    <span class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-xs shrink-0"
                          style="background-color: {{ $f['brand'] }}">{{ $f['short'] }}</span>
                    <span class="font-semibold text-gray-900 text-sm leading-tight">{{ $f['name'] }}</span>
                </div>
                <p class="text-xs text-gray-400 flex-1">{{ $f['note'] }}</p>
                <a href="{{ $readyCount ? route('accounting.payments.bankfile.download', $f['key']) : '#' }}"
                   class="mt-3 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg
                          {{ $readyCount ? 'bg-gray-900 text-white hover:bg-gray-800' : 'bg-gray-100 text-gray-400 cursor-not-allowed pointer-events-none' }}">
                    <i class="fa fa-download"></i> Download
                </a>
            </div>
        </div>
        @endforeach

        {{-- User-uploaded custom formats --}}
        @foreach($templates as $t)
        <div class="bg-white rounded-xl border border-navy-200 shadow-sm overflow-hidden flex flex-col">
            <div class="h-1.5 bg-navy-600"></div>
            <div class="p-4 flex flex-col flex-1">
                <div class="flex items-center gap-2.5 mb-2">
                    <span class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-xs shrink-0 bg-navy-600"><i class="fa fa-file-csv"></i></span>
                    <span class="font-semibold text-gray-900 text-sm leading-tight">{{ $t->name }}</span>
                </div>
                <p class="text-xs text-gray-400 flex-1">Your format · {{ count($t->headers) }} columns</p>
                <div class="mt-3 flex items-center gap-2">
                    <a href="{{ $readyCount ? route('accounting.payments.bankfile.download', 'custom-'.$t->id) : '#' }}"
                       class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg
                              {{ $readyCount ? 'bg-navy-600 text-white hover:bg-navy-700' : 'bg-gray-100 text-gray-400 cursor-not-allowed pointer-events-none' }}">
                        <i class="fa fa-download"></i> Download
                    </a>
                    <form method="POST" action="{{ route('accounting.payments.bankfile.template.delete', $t) }}" onsubmit="return confirm('Delete this format?');">
                        @csrf @method('DELETE')
                        <button class="px-2.5 py-2 text-gray-400 hover:text-red-600 rounded-lg"><i class="fa fa-trash text-xs"></i></button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Upload your bank's own prescribed format --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center gap-2 mb-1">
            <i class="fa fa-file-arrow-up text-navy-500"></i>
            <h3 class="font-semibold text-gray-800 text-sm">Upload your bank's format</h3>
        </div>
        <p class="text-xs text-gray-500 mb-3">Have a sample file your bank prescribed? Upload it (a CSV with the header row) — we'll read its columns and let you map each one, then generate payment files in exactly that layout.</p>
        <form method="POST" action="{{ route('accounting.payments.bankfile.template.upload') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
            @csrf
            <input type="text" name="name" placeholder="Format name (e.g. My HDFC layout)" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="file" name="file" accept=".csv,.txt" required
                   class="text-xs file:mr-2 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-navy-50 file:text-navy-700 file:text-xs file:font-medium hover:file:bg-navy-100">
            <button class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg"><i class="fa fa-arrow-right mr-1.5"></i>Read columns</button>
        </form>
    </div>

    <p class="text-xs text-gray-400 flex items-start gap-2">
        <i class="fa fa-circle-info mt-0.5"></i>
        <span>The built-in layouts follow each bank's published bulk-upload template. Corporate portals sometimes differ by version or your host-to-host setup — upload your bank's own sample above and map it once for an exact match. Column names are provided; brand marks identify each bank only.</span>
    </p>

    {{-- Vendors missing bank details --}}
    @if($missing->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
            <i class="fa fa-triangle-exclamation text-orange-500"></i>
            <h3 class="font-semibold text-gray-800 text-sm">Bills excluded — vendor bank details missing</h3>
            <span class="text-xs text-gray-400">({{ $missing->count() }})</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Bill #</th>
                    <th class="text-left px-5 py-2.5">Vendor</th>
                    <th class="text-right px-5 py-2.5">Balance</th>
                    <th class="text-right px-5 py-2.5">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($missing as $inv)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3"><a href="{{ route('accounting.invoices.show', $inv) }}" class="text-navy-600 font-medium hover:underline">{{ $inv->invoice_number }}</a></td>
                    <td class="px-5 py-3 text-gray-700">{{ $inv->party?->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-right text-orange-600">₹{{ number_format($inv->balance_amount, 2) }}</td>
                    <td class="px-5 py-3 text-right">
                        @if($inv->party)
                        <a href="{{ route('accounting.parties.edit', $inv->party) }}" class="text-xs px-2.5 py-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                            <i class="fa fa-plus mr-1"></i>Add bank details
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
