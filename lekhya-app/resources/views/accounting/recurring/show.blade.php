@extends('layouts.app')
@section('title', 'Recurring Schedule')
@section('page-title', 'Recurring Schedule')

@section('content')
@php $h = $recurring->header ?? []; @endphp
<div class="py-4 space-y-5 max-w-5xl">
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('accounting.recurring.index') }}" class="hover:underline"><i class="fa fa-arrow-left mr-1"></i>Recurring invoices</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $recurring->title }}</h2>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $recurring->status === 'active' ? 'bg-green-100 text-green-700' : ($recurring->status === 'paused' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500') }}">
                        {{ ucfirst($recurring->status) }}
                    </span>
                    @if($recurring->auto_post)<span class="text-[10px] px-1.5 py-0.5 rounded bg-green-50 text-green-600 font-medium">Auto-post</span>@endif
                </div>
                <p class="text-sm text-gray-500 mt-1">{{ $recurring->party->name ?? '—' }} · {{ $recurring->frequencyLabel() }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($recurring->status !== 'ended')
                <form method="POST" action="{{ route('accounting.recurring.run', $recurring) }}" onsubmit="return confirm('Raise the next draft invoice now?')">
                    @csrf
                    <button class="px-3 py-1.5 text-sm rounded-lg bg-navy-600 hover:bg-navy-700 text-white font-medium"><i class="fa fa-bolt mr-1.5"></i>Raise now</button>
                </form>
                @if($recurring->status === 'active')
                <form method="POST" action="{{ route('accounting.recurring.pause', $recurring) }}">
                    @csrf
                    <button class="px-3 py-1.5 text-sm rounded-lg border border-amber-300 text-amber-700 hover:bg-amber-50"><i class="fa fa-pause mr-1.5"></i>Pause</button>
                </form>
                @else
                <form method="POST" action="{{ route('accounting.recurring.resume', $recurring) }}">
                    @csrf
                    <button class="px-3 py-1.5 text-sm rounded-lg border border-green-300 text-green-700 hover:bg-green-50"><i class="fa fa-play mr-1.5"></i>Resume</button>
                </form>
                @endif
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <div><p class="text-xs text-gray-400 uppercase tracking-wide">Next invoice</p><p class="text-sm font-medium text-gray-800 mt-0.5">{{ $recurring->status === 'ended' ? '—' : optional($recurring->next_run_date)->format('d M Y') }}</p></div>
            <div><p class="text-xs text-gray-400 uppercase tracking-wide">Started</p><p class="text-sm font-medium text-gray-800 mt-0.5">{{ optional($recurring->start_date)->format('d M Y') }}</p></div>
            <div><p class="text-xs text-gray-400 uppercase tracking-wide">Ends</p><p class="text-sm font-medium text-gray-800 mt-0.5">{{ $recurring->end_date ? $recurring->end_date->format('d M Y') : ($recurring->occurrences_limit ? 'After '.$recurring->occurrences_limit.' invoices' : 'No end') }}</p></div>
            <div><p class="text-xs text-gray-400 uppercase tracking-wide">Invoice amount</p><p class="text-sm font-semibold text-gray-900 mt-0.5">₹{{ number_format((float) ($h['total_amount'] ?? 0), 2) }}</p></div>
        </div>
    </div>

    {{-- Snapshot of the lines each invoice will carry --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100"><h3 class="text-sm font-semibold text-gray-700">Line items <span class="font-normal text-gray-400">— copied to every invoice</span></h3></div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2">Description</th>
                    <th class="text-left px-5 py-2">HSN/SAC</th>
                    <th class="text-right px-5 py-2">Qty</th>
                    <th class="text-right px-5 py-2">Rate</th>
                    <th class="text-right px-5 py-2">GST%</th>
                    <th class="text-right px-5 py-2">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach(($recurring->lines ?? []) as $l)
                <tr>
                    <td class="px-5 py-2.5 text-gray-800">{{ $l['description'] ?? '—' }}</td>
                    <td class="px-5 py-2.5 text-gray-400 font-mono text-xs">{{ $l['hsn_sac_code'] ?? '—' }}</td>
                    <td class="px-5 py-2.5 text-right text-gray-600">{{ rtrim(rtrim(number_format((float)($l['quantity'] ?? 0), 2), '0'), '.') }} {{ $l['unit'] ?? '' }}</td>
                    <td class="px-5 py-2.5 text-right text-gray-600">₹{{ number_format((float)($l['rate'] ?? 0), 2) }}</td>
                    <td class="px-5 py-2.5 text-right text-gray-500">{{ (float)(($l['cgst_rate'] ?? 0) + ($l['sgst_rate'] ?? 0) + ($l['igst_rate'] ?? 0)) }}%</td>
                    <td class="px-5 py-2.5 text-right font-medium text-gray-900">₹{{ number_format((float)($l['line_total'] ?? 0), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Invoices already raised from this schedule --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">Invoices raised</h3>
            <span class="text-xs text-gray-400">{{ $recurring->invoices->count() }} total</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2">Invoice #</th>
                    <th class="text-left px-5 py-2">Date</th>
                    <th class="text-right px-5 py-2">Amount</th>
                    <th class="text-right px-5 py-2">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($recurring->invoices as $inv)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-2.5"><a href="{{ route('accounting.invoices.show', $inv) }}" class="text-navy-600 font-medium hover:underline">{{ $inv->invoice_number }}</a></td>
                    <td class="px-5 py-2.5 text-gray-500">{{ optional($inv->invoice_date)->format('d M Y') }}</td>
                    <td class="px-5 py-2.5 text-right font-medium text-gray-900">₹{{ number_format((float) $inv->total_amount, 2) }}</td>
                    <td class="px-5 py-2.5 text-right">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $inv->status === 'posted' ? 'bg-green-100 text-green-700' : ($inv->status === 'draft' ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700') }}">{{ ucfirst($inv->status) }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">No invoices raised yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-end">
        <form method="POST" action="{{ route('accounting.recurring.destroy', $recurring) }}" onsubmit="return confirm('Delete this recurring schedule? Invoices already raised are kept.')">
            @csrf @method('DELETE')
            <button class="px-3 py-1.5 text-sm rounded-lg border border-red-200 text-red-600 hover:bg-red-50"><i class="fa fa-trash mr-1.5"></i>Delete schedule</button>
        </form>
    </div>
</div>
@endsection
