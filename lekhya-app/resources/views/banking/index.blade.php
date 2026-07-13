@extends('layouts.app')
@section('title', 'Banking')
@section('page-title', 'Bank Reconciliation')

@section('content')
<div class="py-4 space-y-6 max-w-4xl" x-data="{ showAdd: {{ $bankAccounts->isEmpty() ? 'true' : 'false' }} }">

    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Match your bank statement against the books, line by line, until every entry is accounted for.</p>
        <button @click="showAdd = !showAdd" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg whitespace-nowrap">
            <i class="fa fa-plus mr-1.5"></i>Add bank account
        </button>
    </div>

    {{-- How it works --}}
    <div class="bg-navy-50 border border-navy-100 rounded-xl p-5">
        <h3 class="font-semibold text-navy-800 text-sm mb-2"><i class="fa fa-circle-info mr-1.5"></i>How reconciliation works</h3>
        <ol class="text-sm text-navy-900/80 space-y-1 list-decimal list-inside">
            <li>Add your bank account and link it to its ledger in the Chart of Accounts.</li>
            <li>Download your statement as CSV from net-banking and <strong>import</strong> it here.</li>
            <li>Lekhya suggests the matching book entry for each line (same amount, nearest date) — <strong>accept</strong> or pick another.</li>
            <li>Money <em>in</em> on the statement matches a debit to the bank ledger; money <em>out</em> matches a credit.</li>
            <li>When every line is matched and the closing balance agrees, <strong>lock</strong> the reconciliation.</li>
        </ol>
    </div>

    {{-- Add account form --}}
    <div x-show="showAdd" x-cloak x-transition class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Add a bank account</h3>
        @if($ledgers->isEmpty())
            <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
                No bank/cash ledger found. Create one under <a href="{{ route('accounting.accounts.index') }}" class="underline font-medium">Chart of Accounts</a> first, then add the bank account here.
            </p>
        @else
        <form method="POST" action="{{ route('banking.accounts.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bank name <span class="text-red-500">*</span></label>
                <input type="text" name="bank_name" required value="{{ old('bank_name') }}" placeholder="HDFC Bank — Current" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account number <span class="text-red-500">*</span></label>
                <input type="text" name="account_number" required value="{{ old('account_number') }}" maxlength="34" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">IFSC</label>
                <input type="text" name="ifsc_code" value="{{ old('ifsc_code') }}" maxlength="11" placeholder="HDFC0001234" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                <input type="text" name="branch" value="{{ old('branch') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Linked ledger account <span class="text-red-500">*</span></label>
                <select name="account_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Select ledger…</option>
                    @foreach($ledgers as $l)
                    <option value="{{ $l->id }}" @selected(old('account_id') == $l->id)>{{ $l->code }} — {{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Opening balance</label>
                <input type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', 0) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="px-5 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">Save bank account</button>
            </div>
        </form>
        @endif
    </div>

    {{-- Bank accounts --}}
    @forelse($bankAccounts as $ba)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-lg bg-navy-50 flex items-center justify-center">
                    <i class="fa fa-building-columns text-navy-600"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ $ba->bank_name }}</p>
                    <p class="text-xs text-gray-500 font-mono">
                        {{ $ba->account_number ? '••••' . substr($ba->account_number, -4) : '—' }}
                        @if($ba->ifsc_code) · {{ $ba->ifsc_code }}@endif
                        @if($ba->account) · {{ $ba->account->name }}@endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-right">
                    <p class="text-xs text-gray-400 uppercase tracking-wider">Unreconciled</p>
                    <p class="text-lg font-bold {{ ($stats[$ba->id]['unreconciled'] ?? 0) > 0 ? 'text-orange-600' : 'text-green-600' }}">
                        {{ $stats[$ba->id]['unreconciled'] ?? 0 }}<span class="text-xs text-gray-400 font-normal"> / {{ $stats[$ba->id]['total'] ?? 0 }}</span>
                    </p>
                </div>
                <a href="{{ route('banking.reconcile', $ba) }}" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
                    Reconcile <i class="fa fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-10 text-center text-gray-400">
        <i class="fa fa-building-columns text-3xl mb-3"></i>
        <p class="text-sm">No bank accounts yet. Add one above to begin reconciling.</p>
    </div>
    @endforelse
</div>
@endsection
