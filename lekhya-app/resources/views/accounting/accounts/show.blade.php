@extends('layouts.app')
@section('title', $account->name)
@section('page-title', $account->name)

@section('content')
@php $balance = $account->getBalance(); @endphp
<div class="py-4 space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-gray-400 font-mono text-sm">{{ $account->code }}</span>
            <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-gray-100 text-gray-600 capitalize">{{ $account->type }}</span>
            @if($account->is_system)<span class="text-xs px-2.5 py-1 rounded-full font-medium bg-navy-50 text-navy-700">system account</span>@endif
        </div>
        <div class="flex gap-2">
            @if($account->is_ledger)
            <a href="{{ route('accounting.accounts.ledger', $account) }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                <i class="fa fa-list mr-1.5"></i>View Ledger
            </a>
            @endif
            @if(!$account->is_system)
            <a href="{{ route('accounting.accounts.edit', $account) }}" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
                <i class="fa fa-pen mr-1.5"></i>Edit
            </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Debit</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">₹{{ number_format($balance['debit'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Credit</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">₹{{ number_format($balance['credit'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Net Balance</p>
            <p class="text-2xl font-bold {{ $balance['net'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                ₹{{ number_format(abs($balance['net']), 2) }} {{ $balance['net'] >= 0 ? 'Dr' : 'Cr' }}
            </p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div><dt class="text-gray-500">Sub-type</dt><dd class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $account->sub_type) ?: '—' }}</dd></div>
            <div><dt class="text-gray-500">Parent</dt><dd class="font-medium text-gray-900">{{ $account->parent->name ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Ledger account?</dt><dd class="font-medium text-gray-900">{{ $account->is_ledger ? 'Yes' : 'No — group/heading' }}</dd></div>
            <div><dt class="text-gray-500">Opening Balance</dt><dd class="font-medium text-gray-900">₹{{ number_format($account->opening_balance, 2) }} {{ ucfirst($account->opening_balance_type) }}</dd></div>
        </dl>
        @if($account->description)
        <p class="text-sm text-gray-500 mt-4 pt-4 border-t border-gray-100">{{ $account->description }}</p>
        @endif
    </div>
</div>
@endsection
