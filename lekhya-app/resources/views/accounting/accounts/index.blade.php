@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('page-title', 'Chart of Accounts')

@section('content')
@php
    $groups = $accounts->groupBy('type');
    $typeLabels = ['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expenses'];
    $typeColors = ['asset' => 'blue', 'liability' => 'red', 'equity' => 'purple', 'revenue' => 'green', 'expense' => 'orange'];
@endphp
<div class="py-4 space-y-6">
    <div class="flex items-center justify-end">
        <a href="{{ route('accounting.accounts.create') }}" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
            <i class="fa fa-plus mr-1.5"></i>New Account
        </a>
    </div>

    @foreach($typeLabels as $type => $label)
    @if($groups->has($type))
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="p-5 border-b border-gray-100 flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-{{ $typeColors[$type] }}-500"></span>
            <h3 class="font-semibold text-gray-900">{{ $label }}</h3>
            <span class="text-xs text-gray-400">{{ $groups[$type]->count() }} accounts</span>
        </div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-50">
                @foreach($groups[$type]->sortBy('code') as $acc)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-2.5 w-24 text-gray-400 font-mono text-xs">{{ $acc->code }}</td>
                    <td class="px-5 py-2.5">
                        <a href="{{ route('accounting.accounts.show', $acc) }}" class="{{ $acc->is_ledger ? 'text-navy-600 font-medium hover:underline' : 'text-gray-500 font-semibold' }}">
                            {{ $acc->parent ? '— ' : '' }}{{ $acc->name }}
                        </a>
                        @if($acc->is_system)<span class="ml-2 text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">system</span>@endif
                        @if(!$acc->is_ledger)<span class="ml-2 text-xs px-1.5 py-0.5 rounded bg-gray-50 text-gray-400 border border-gray-200">group</span>@endif
                    </td>
                    <td class="px-5 py-2.5 text-right">
                        @if($acc->is_ledger)
                        <a href="{{ route('accounting.accounts.ledger', $acc) }}" class="text-xs text-blue-600 hover:text-blue-700">View ledger →</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endforeach
</div>
@endsection
