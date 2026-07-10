@extends('layouts.app')
@section('title', $journal->voucher_number)
@section('page-title', $journal->voucher_number)

@section('content')
<div class="py-4 space-y-6 max-w-4xl">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-gray-100 text-gray-600 capitalize">{{ $journal->voucher_type }}</span>
            @if($journal->is_reversed)
            <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-red-100 text-red-700">Reversed</span>
            @elseif($journal->is_posted)
            <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-green-100 text-green-700">Posted</span>
            @endif
            <span class="text-gray-500 text-sm">{{ $journal->date->format('d M Y') }}</span>
        </div>
        @if($journal->is_posted && !$journal->is_reversed)
        <div x-data="{ open: false }">
            <button @click="open = true" class="px-4 py-2 border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50">
                <i class="fa fa-rotate-left mr-1.5"></i>Reverse
            </button>
            <div x-show="open" x-cloak class="fixed inset-0 bg-black/30 flex items-center justify-center z-50" @click.self="open = false">
                <div class="bg-white rounded-xl p-6 w-full max-w-sm">
                    <h3 class="font-semibold text-gray-900 mb-3">Reverse this journal?</h3>
                    <form method="POST" action="{{ route('accounting.journals.reverse', $journal) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Reversal Date</label>
                            <input type="date" name="date" required value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Reason (optional)</label>
                            <input type="text" name="reason" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div class="flex gap-2 pt-1">
                            <button type="submit" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg">Confirm Reversal</button>
                            <button type="button" @click="open = false" class="px-4 py-2 text-gray-600 text-sm">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <p class="text-gray-700">{{ $journal->narration }}</p>
        @if($journal->reference)<p class="text-xs text-gray-400 mt-1">Ref: {{ $journal->reference }}</p>@endif
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Account</th>
                    <th class="text-left px-5 py-2.5">Narration</th>
                    <th class="text-right px-5 py-2.5">Debit</th>
                    <th class="text-right px-5 py-2.5">Credit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($journal->lines as $line)
                <tr>
                    <td class="px-5 py-3 font-medium text-gray-900">{{ $line->account->code }} — {{ $line->account->name }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $line->narration }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ $line->debit > 0 ? '₹' . number_format($line->debit, 2) : '—' }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ $line->credit > 0 ? '₹' . number_format($line->credit, 2) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-semibold">
                <tr>
                    <td colspan="2" class="px-5 py-3 text-right text-gray-600">Total</td>
                    <td class="px-5 py-3 text-right text-gray-900">₹{{ number_format($journal->total_debit, 2) }}</td>
                    <td class="px-5 py-3 text-right text-gray-900">₹{{ number_format($journal->total_credit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($journal->reversedBy)
    <p class="text-sm text-gray-500">Reversed by <a href="{{ route('accounting.journals.show', $journal->reversedBy) }}" class="text-blue-600 hover:underline">{{ $journal->reversedBy->voucher_number }}</a></p>
    @endif
</div>
@endsection
