@extends('layouts.app')
@section('title', 'Day Book')
@section('page-title', 'Day Book')

@section('content')
<div class="py-4 space-y-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <a href="{{ route('accounting.reports.index') }}" class="text-sm text-navy-600 hover:underline mr-2">← All reports</a>
        <div><label class="block text-xs text-gray-500 mb-1">From</label><input type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-gray-500 mb-1">To</label><input type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
        <button class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">Apply</button>
        <span class="ml-auto text-sm text-gray-500">Total turnover: <strong class="text-gray-900">₹{{ number_format($totalDebit, 2) }}</strong></span>
    </form>

    <div class="flex justify-end">
        <x-report-share type="day-book" :filters="['from' => $from, 'to' => $to]" />
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Date</th>
                    <th class="text-left px-5 py-2.5">Voucher</th>
                    <th class="text-left px-5 py-2.5">Account</th>
                    <th class="text-left px-5 py-2.5">Narration</th>
                    <th class="text-right px-5 py-2.5">Debit</th>
                    <th class="text-right px-5 py-2.5">Credit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($journals as $j)
                    @foreach($j->lines as $i => $line)
                    <tr class="{{ $i === 0 ? 'border-t-2 border-gray-100' : '' }}">
                        <td class="px-5 py-2 text-gray-500 whitespace-nowrap">{{ $i === 0 ? $j->date->format('d M Y') : '' }}</td>
                        <td class="px-5 py-2 whitespace-nowrap">@if($i === 0)<a href="{{ route('accounting.journals.show', $j) }}" class="text-navy-600 hover:underline">{{ $j->voucher_number }}</a>@endif</td>
                        <td class="px-5 py-2 text-gray-800"><span class="font-mono text-xs text-gray-400 mr-1">{{ $line->account->code ?? '' }}</span>{{ $line->account->name ?? '—' }}</td>
                        <td class="px-5 py-2 text-gray-400 text-xs">{{ $line->narration }}</td>
                        <td class="px-5 py-2 text-right {{ $line->debit > 0 ? 'text-gray-900' : 'text-gray-300' }}">{{ $line->debit > 0 ? '₹'.number_format($line->debit, 2) : '' }}</td>
                        <td class="px-5 py-2 text-right {{ $line->credit > 0 ? 'text-gray-900' : 'text-gray-300' }}">{{ $line->credit > 0 ? '₹'.number_format($line->credit, 2) : '' }}</td>
                    </tr>
                    @endforeach
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No vouchers in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
