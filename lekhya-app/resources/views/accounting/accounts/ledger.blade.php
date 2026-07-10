@extends('layouts.app')
@section('title', $account->name . ' — Ledger')
@section('page-title', $account->name . ' — Ledger')

@section('content')
<div class="py-4 space-y-6">
    <form method="GET" class="flex items-end gap-3">
        <div>
            <label class="block text-xs text-gray-500 mb-1">From</label>
            <input type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">To</label>
            <input type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
        </div>
        <button class="px-4 py-1.5 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">Filter</button>
        <a href="{{ route('accounting.accounts.show', $account) }}" class="text-sm text-gray-500 hover:text-gray-700 ml-2">← Back to account</a>
    </form>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Date</th>
                    <th class="text-left px-5 py-2.5">Voucher</th>
                    <th class="text-left px-5 py-2.5">Narration</th>
                    <th class="text-right px-5 py-2.5">Debit</th>
                    <th class="text-right px-5 py-2.5">Credit</th>
                    <th class="text-right px-5 py-2.5">Balance</th>
                </tr>
            </thead>
            @php $running = 0; @endphp
            <tbody class="divide-y divide-gray-50">
                @forelse($lines as $line)
                @php $running += $line->debit - $line->credit; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-2.5 text-gray-500">{{ $line->journal->date->format('d M Y') }}</td>
                    <td class="px-5 py-2.5">
                        <a href="{{ route('accounting.journals.show', $line->journal_id) }}" class="text-blue-600 hover:underline">{{ $line->journal->voucher_number }}</a>
                    </td>
                    <td class="px-5 py-2.5 text-gray-700">{{ $line->narration ?: $line->journal->narration }}</td>
                    <td class="px-5 py-2.5 text-right text-gray-700">{{ $line->debit > 0 ? '₹' . number_format($line->debit, 2) : '—' }}</td>
                    <td class="px-5 py-2.5 text-right text-gray-700">{{ $line->credit > 0 ? '₹' . number_format($line->credit, 2) : '—' }}</td>
                    <td class="px-5 py-2.5 text-right font-medium {{ $running >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                        ₹{{ number_format(abs($running), 2) }} {{ $running >= 0 ? 'Dr' : 'Cr' }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No posted transactions in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
