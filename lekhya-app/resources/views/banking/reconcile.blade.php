@extends('layouts.app')
@section('title', 'Reconcile · ' . $bankAccount->bank_name)
@section('page-title', 'Reconcile — ' . $bankAccount->bank_name)

@section('content')
@php
    $unrec = $transactions->getCollection()->where('status', 'unreconciled')->count();
@endphp
<div class="py-4 space-y-6" x-data="{ showImport: {{ $transactions->total() === 0 ? 'true' : 'false' }} }">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('banking.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <i class="fa fa-arrow-left mr-1.5"></i>All bank accounts
        </a>
        <button @click="showImport = !showImport" class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg">
            <i class="fa fa-upload mr-1.5"></i>Import statement
        </button>
    </div>

    {{-- Import statement form --}}
    <div x-show="showImport" x-cloak x-transition class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-1">Import a bank statement (CSV)</h3>
        <p class="text-xs text-gray-500 mb-4">Tell us which column holds each field (0 = first column). Defaults suit most exports.</p>
        <form method="POST" action="{{ route('banking.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="bank_account_id" value="{{ $bankAccount->id }}">
            <input type="file" name="file" accept=".csv,.txt" required
                   class="text-sm file:mr-2 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-navy-50 file:text-navy-700 file:text-xs file:font-medium hover:file:bg-navy-100">
            <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
                @foreach(['date_col' => ['Date col', 0], 'desc_col' => ['Description col', 1], 'debit_col' => ['Debit col', 2], 'credit_col' => ['Credit col', 3], 'balance_col' => ['Balance col', 4], 'skip_rows' => ['Header rows', 1]] as $name => [$label, $default])
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
                    <input type="number" min="0" name="{{ $name }}" value="{{ $default }}" class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm">
                </div>
                @endforeach
            </div>
            <button type="submit" class="px-5 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">Import transactions</button>
        </form>
    </div>

    {{-- Progress --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Statement lines</p>
            <p class="text-xl font-bold text-gray-900 mt-1">{{ $transactions->total() }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Unreconciled (page)</p>
            <p class="text-xl font-bold {{ $unrec > 0 ? 'text-orange-600' : 'text-green-600' }} mt-1">{{ $unrec }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Auto-match found</p>
            <p class="text-xl font-bold text-navy-700 mt-1">{{ count($suggestions) }}</p>
        </div>
    </div>

    {{-- Transactions --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Date</th>
                    <th class="text-left px-5 py-2.5">Description</th>
                    <th class="text-right px-5 py-2.5">Money out</th>
                    <th class="text-right px-5 py-2.5">Money in</th>
                    <th class="text-left px-5 py-2.5">Suggested match</th>
                    <th class="text-center px-5 py-2.5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($transactions as $txn)
                @php $sug = $suggestions[$txn->id] ?? null; @endphp
                <tr class="hover:bg-gray-50" id="txn-{{ $txn->id }}">
                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $txn->transaction_date?->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-gray-800">{{ $txn->description ?: '—' }}</td>
                    <td class="px-5 py-3 text-right {{ $txn->debit > 0 ? 'text-red-600' : 'text-gray-300' }}">{{ $txn->debit > 0 ? '₹' . number_format($txn->debit, 2) : '—' }}</td>
                    <td class="px-5 py-3 text-right {{ $txn->credit > 0 ? 'text-green-600' : 'text-gray-300' }}">{{ $txn->credit > 0 ? '₹' . number_format($txn->credit, 2) : '—' }}</td>
                    <td class="px-5 py-3">
                        @if($txn->status === 'reconciled')
                            <span class="text-xs text-gray-400">Matched to voucher</span>
                        @elseif($sug)
                            <div class="text-xs text-gray-600">
                                <span class="font-medium text-gray-800">{{ optional($sug->journal)->voucher_number ?? 'Voucher' }}</span>
                                · {{ optional($sug->journal)->date?->format('d M') }}
                                <span class="text-gray-400">{{ \Illuminate\Support\Str::limit($sug->narration ?: (optional($sug->journal)->narration ?? ''), 40) }}</span>
                            </div>
                        @else
                            <span class="text-xs text-gray-300">No match found</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center whitespace-nowrap">
                        @if($txn->status === 'reconciled')
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700"><i class="fa fa-check mr-1"></i>Reconciled</span>
                        @elseif($sug)
                            <button type="button"
                                    onclick="matchTxn({{ $txn->id }}, {{ $sug->id }}, this)"
                                    class="text-xs px-3 py-1 rounded-lg bg-navy-600 hover:bg-navy-700 text-white font-medium">
                                <i class="fa fa-link mr-1"></i>Match
                            </button>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-500">Open</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">No transactions imported yet. Use “Import statement” above.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($transactions->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $transactions->links() }}</div>
        @endif
    </div>

    {{-- Complete / lock --}}
    @if($transactions->total() > 0)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-1">Lock this reconciliation</h3>
        <p class="text-xs text-gray-500 mb-4">Once the closing balance on your statement matches the books, record and lock the reconciliation.</p>
        <form method="POST" action="{{ route('banking.complete') }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <input type="hidden" name="bank_account_id" value="{{ $bankAccount->id }}">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Statement date</label>
                <input type="date" name="statement_date" required value="{{ now()->format('Y-m-d') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Closing balance (₹)</label>
                <input type="number" step="0.01" name="statement_balance" required placeholder="0.00" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <button type="submit" onclick="return confirm('Lock this reconciliation? This records the statement balance.');"
                    class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg">
                <i class="fa fa-lock mr-1.5"></i>Complete &amp; lock
            </button>
        </form>
    </div>
    @endif
</div>

@push('scripts')
<script>
function matchTxn(txnId, journalLineId, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-circle-notch fa-spin"></i>';
    fetch(@js(route('banking.match')), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ bank_transaction_id: txnId, journal_line_id: journalLineId })
    }).then(function (r) {
        if (!r.ok) throw new Error('match failed');
        return r.json();
    }).then(function () {
        var row = document.getElementById('txn-' + txnId);
        if (row) row.querySelector('td:last-child').innerHTML =
            '<span class="text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700"><i class="fa fa-check mr-1"></i>Reconciled</span>';
    }).catch(function () {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-triangle-exclamation mr-1"></i>Retry';
    });
}
</script>
@endpush
@endsection
