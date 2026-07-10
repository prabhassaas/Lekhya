@extends('layouts.app')
@section('title', 'Journal Vouchers')
@section('page-title', 'Journal Vouchers')

@section('content')
<div class="py-4 space-y-6">
    <div class="flex items-center justify-end">
        <a href="{{ route('accounting.journals.create') }}" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
            <i class="fa fa-plus mr-1.5"></i>New Journal
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Voucher #</th>
                    <th class="text-left px-5 py-2.5">Date</th>
                    <th class="text-left px-5 py-2.5">Type</th>
                    <th class="text-left px-5 py-2.5">Narration</th>
                    <th class="text-right px-5 py-2.5">Amount</th>
                    <th class="text-left px-5 py-2.5">Created By</th>
                    <th class="text-right px-5 py-2.5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($journals as $j)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3"><a href="{{ route('accounting.journals.show', $j) }}" class="text-navy-600 font-medium hover:underline">{{ $j->voucher_number }}</a></td>
                    <td class="px-5 py-3 text-gray-500">{{ $j->date->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-gray-500 capitalize">{{ $j->voucher_type }}</td>
                    <td class="px-5 py-3 text-gray-700 max-w-xs truncate">{{ $j->narration }}</td>
                    <td class="px-5 py-3 text-right font-medium text-gray-900">₹{{ number_format($j->total_debit, 2) }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $j->createdBy->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-right">
                        @if($j->is_reversed)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-red-100 text-red-700">Reversed</span>
                        @elseif($j->is_posted)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700">Posted</span>
                        @else
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-600">Draft</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">No journal vouchers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($journals->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $journals->links() }}</div>
        @endif
    </div>
</div>
@endsection
