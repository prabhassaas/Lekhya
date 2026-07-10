@extends('layouts.app')
@section('title', 'Trial Balance')
@section('page-title', 'Trial Balance')

@section('content')
@php
    $totalDebit = collect($accounts)->sum(fn($a) => $a['net'] > 0 ? $a['net'] : 0);
    $totalCredit = collect($accounts)->sum(fn($a) => $a['net'] < 0 ? abs($a['net']) : 0);
@endphp
<div class="py-4 space-y-6">
    <form method="GET" class="flex items-end gap-3">
        <div><label class="block text-xs text-gray-500 mb-1">As of</label><input type="date" name="as_of" value="{{ $asOf }}" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm"></div>
        <button class="px-4 py-1.5 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">Filter</button>
        <a href="{{ route('accounting.reports.pdf', 'trial-balance') }}?as_of={{ $asOf }}" class="ml-auto px-4 py-1.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
            <i class="fa fa-file-pdf mr-1.5"></i>Export PDF
        </a>
    </form>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Code</th>
                    <th class="text-left px-5 py-2.5">Account</th>
                    <th class="text-left px-5 py-2.5">Type</th>
                    <th class="text-right px-5 py-2.5">Debit</th>
                    <th class="text-right px-5 py-2.5">Credit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($accounts as $acc)
                @continue($acc['net'] == 0)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-2.5 text-gray-400 font-mono text-xs">{{ $acc['code'] }}</td>
                    <td class="px-5 py-2.5 text-gray-900">{{ $acc['name'] }}</td>
                    <td class="px-5 py-2.5 text-gray-500 capitalize">{{ $acc['type'] }}</td>
                    <td class="px-5 py-2.5 text-right text-gray-700">{{ $acc['net'] > 0 ? '₹' . number_format($acc['net'], 2) : '—' }}</td>
                    <td class="px-5 py-2.5 text-right text-gray-700">{{ $acc['net'] < 0 ? '₹' . number_format(abs($acc['net']), 2) : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No ledger accounts with activity.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-semibold">
                <tr>
                    <td colspan="3" class="px-5 py-3 text-right text-gray-600">Total</td>
                    <td class="px-5 py-3 text-right text-gray-900">₹{{ number_format($totalDebit, 2) }}</td>
                    <td class="px-5 py-3 text-right text-gray-900">₹{{ number_format($totalCredit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @if(abs($totalDebit - $totalCredit) > 0.01)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
        <i class="fa fa-triangle-exclamation mr-1.5"></i>Trial balance doesn't balance — debit and credit totals differ by ₹{{ number_format(abs($totalDebit - $totalCredit), 2) }}.
    </div>
    @endif
</div>
@endsection
