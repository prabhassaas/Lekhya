@extends('layouts.app')
@section('title', isset($account) ? 'Edit Account' : 'New Account')
@section('page-title', isset($account) ? 'Edit Account' : 'New Account')

@section('content')
<div class="py-4 max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="{{ isset($account) ? route('accounting.accounts.update', $account) : route('accounting.accounts.store') }}" class="space-y-4">
            @csrf
            @if(isset($account))@method('PUT')@endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" required value="{{ old('code', $account->code ?? '') }}"
                           {{ isset($account) ? 'readonly' : '' }}
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm {{ isset($account) ? 'bg-gray-50 text-gray-500' : '' }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                    <select name="type" required {{ isset($account) ? 'disabled' : '' }}
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm {{ isset($account) ? 'bg-gray-50 text-gray-500' : '' }}">
                        @foreach(['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expense'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('type', $account->type ?? '') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required value="{{ old('name', $account->name ?? '') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Parent Group (optional)</label>
                <select name="parent_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">— None —</option>
                    @foreach($parents as $p)
                    <option value="{{ $p->id }}" @selected(old('parent_id', $account->parent_id ?? '') == $p->id)>{{ $p->code }} — {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_ledger" id="is_ledger" value="1" {{ old('is_ledger', $account->is_ledger ?? true) ? 'checked' : '' }}
                       class="rounded border-gray-300">
                <label for="is_ledger" class="text-sm text-gray-700">This is a postable ledger account (uncheck for a group/heading)</label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('description', $account->description ?? '') }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">
                    {{ isset($account) ? 'Save Changes' : 'Create Account' }}
                </button>
                <a href="{{ route('accounting.accounts.index') }}" class="px-5 py-2.5 text-gray-600 text-sm font-medium hover:text-gray-900">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
