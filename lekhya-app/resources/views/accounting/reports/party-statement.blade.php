@extends('layouts.app')
@section('title', 'Party Statement')
@section('page-title', 'Party Statement')

@section('content')
<div class="py-4 space-y-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <a href="{{ route('accounting.reports.index') }}" class="text-sm text-navy-600 hover:underline mr-2">← All reports</a>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Party</label>
            <select name="party_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-64">
                <option value="">Select a party…</option>
                @foreach($parties as $p)
                <option value="{{ $p->id }}" @selected($party && $party->id === $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="block text-xs text-gray-500 mb-1">From</label><input type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-gray-500 mb-1">To</label><input type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
        <button class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">View</button>
    </form>

    @if($party)
    @php $closing = $rows->last()['balance'] ?? 0; @endphp
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
            <div>
                <p class="font-semibold text-gray-900">{{ $party->name }}</p>
                @if($party->gstin)<p class="text-xs text-gray-400 font-mono">{{ $party->gstin }}</p>@endif
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400 uppercase tracking-wider">Closing balance</p>
                <p class="text-lg font-bold {{ $closing >= 0 ? 'text-gray-900' : 'text-green-700' }}">₹{{ number_format(abs($closing), 2) }} <span class="text-xs font-normal text-gray-400">{{ $closing >= 0 ? 'Dr' : 'Cr' }}</span></p>
            </div>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Date</th>
                    <th class="text-left px-5 py-2.5">Reference</th>
                    <th class="text-left px-5 py-2.5">Particulars</th>
                    <th class="text-right px-5 py-2.5">Debit</th>
                    <th class="text-right px-5 py-2.5">Credit</th>
                    <th class="text-right px-5 py-2.5">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($rows as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-2.5 text-gray-500 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($r['date'])->format('d M Y') }}</td>
                    <td class="px-5 py-2.5 text-gray-700">{{ $r['ref'] }}</td>
                    <td class="px-5 py-2.5 text-gray-600">{{ $r['particulars'] }}</td>
                    <td class="px-5 py-2.5 text-right {{ $r['debit'] > 0 ? 'text-gray-900' : 'text-gray-300' }}">{{ $r['debit'] > 0 ? '₹'.number_format($r['debit'], 2) : '—' }}</td>
                    <td class="px-5 py-2.5 text-right {{ $r['credit'] > 0 ? 'text-gray-900' : 'text-gray-300' }}">{{ $r['credit'] > 0 ? '₹'.number_format($r['credit'], 2) : '—' }}</td>
                    <td class="px-5 py-2.5 text-right font-medium text-gray-800">₹{{ number_format(abs($r['balance']), 2) }} <span class="text-[10px] text-gray-400">{{ $r['balance'] >= 0 ? 'Dr' : 'Cr' }}</span></td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No transactions for this party in the period.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <p class="text-xs text-gray-400">Dr = the party owes you (or you've paid them); Cr = you owe the party (or they've paid you). Includes invoices and recorded settlements.</p>
    @else
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 text-center text-gray-400">Select a party to view its statement.</div>
    @endif
</div>
@endsection
