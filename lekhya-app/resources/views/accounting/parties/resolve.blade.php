@extends('layouts.app')
@section('title', 'Possible duplicate vendor')
@section('page-title', 'Possible duplicate vendor')

@section('content')
@php
    $inGstin = strtoupper(trim($vendor['gstin'] ?? ''));
    $inState = strlen($inGstin) >= 2 ? substr($inGstin, 0, 2) : null;
    $sameState = $existing->state_code && $inState && $existing->state_code === $inState;
@endphp
<div class="py-4 max-w-3xl" x-data="{ choice: 'branch', label: '' }">

    <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 mb-6">
        <p class="text-sm text-amber-800">
            <i class="fa fa-triangle-exclamation mr-1.5"></i>
            A vendor named <strong>{{ $existing->name }}</strong> already exists, but this bill has a
            <strong>different GSTIN</strong>. A vendor and a branch can't share a GSTIN — tell us how to record it.
        </p>
    </div>

    {{-- Side-by-side comparison --}}
    <div class="grid sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Already on file</p>
            <p class="font-semibold text-gray-900">{{ $existing->name }}</p>
            <dl class="mt-3 space-y-1.5 text-sm">
                <div class="flex gap-2"><dt class="text-gray-400 w-20">GSTIN</dt><dd class="font-mono text-gray-700">{{ $existing->gstin ?: '—' }}</dd></div>
                <div class="flex gap-2"><dt class="text-gray-400 w-20">State</dt><dd class="text-gray-700">{{ $existing->state ?: $existing->state_code ?: '—' }}</dd></div>
                <div class="flex gap-2"><dt class="text-gray-400 w-20">Address</dt><dd class="text-gray-700">{{ $existing->address ?: '—' }}</dd></div>
            </dl>
        </div>
        <div class="bg-white rounded-xl border border-navy-200 shadow-sm p-5">
            <p class="text-xs text-navy-500 uppercase tracking-wider mb-2">On this bill</p>
            <p class="font-semibold text-gray-900">{{ $vendor['name'] ?: '—' }}</p>
            <dl class="mt-3 space-y-1.5 text-sm">
                <div class="flex gap-2"><dt class="text-gray-400 w-20">GSTIN</dt><dd class="font-mono text-gray-700">{{ $inGstin ?: '—' }}</dd></div>
                <div class="flex gap-2"><dt class="text-gray-400 w-20">State</dt><dd class="text-gray-700">{{ $inState ?: '—' }}</dd></div>
                <div class="flex gap-2"><dt class="text-gray-400 w-20">Address</dt><dd class="text-gray-700">{{ $vendor['address'] ?: '—' }}</dd></div>
            </dl>
        </div>
    </div>

    <form method="POST" action="{{ route('ai.resolve.store', $suggestion) }}" class="space-y-3">
        @csrf
        <input type="hidden" name="existing" value="{{ $existing->id }}">

        {{-- Branch --}}
        <label class="block bg-white rounded-xl border-2 p-4 cursor-pointer transition-colors"
               :class="choice === 'branch' ? 'border-navy-500 bg-navy-50/40' : 'border-gray-200 hover:border-gray-300'">
            <div class="flex items-start gap-3">
                <input type="radio" name="choice" value="branch" x-model="choice" class="mt-1">
                <div class="flex-1">
                    <p class="font-semibold text-gray-900"><i class="fa fa-code-branch mr-1.5 text-navy-500"></i>Add as a branch of {{ $existing->name }}</p>
                    <p class="text-sm text-gray-500 mt-0.5">Same company, another GST registration/location. Keeps one contact with multiple GSTINs{{ $sameState ? '' : ' across states' }}.</p>
                    <div x-show="choice === 'branch'" x-cloak class="mt-3">
                        <input type="text" name="label" x-model="label" placeholder="Branch label (e.g. Maharashtra Office)"
                               class="w-full sm:w-80 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
        </label>

        {{-- Separate --}}
        <label class="block bg-white rounded-xl border-2 p-4 cursor-pointer transition-colors"
               :class="choice === 'separate' ? 'border-navy-500 bg-navy-50/40' : 'border-gray-200 hover:border-gray-300'">
            <div class="flex items-start gap-3">
                <input type="radio" name="choice" value="separate" x-model="choice" class="mt-1">
                <div class="flex-1">
                    <p class="font-semibold text-gray-900"><i class="fa fa-user-plus mr-1.5 text-navy-500"></i>Create as a separate vendor</p>
                    <p class="text-sm text-gray-500 mt-0.5">A different business that happens to share the name. Adds a brand-new contact.</p>
                </div>
            </div>
        </label>

        {{-- Use existing --}}
        <label class="block bg-white rounded-xl border-2 p-4 cursor-pointer transition-colors"
               :class="choice === 'existing' ? 'border-navy-500 bg-navy-50/40' : 'border-gray-200 hover:border-gray-300'">
            <div class="flex items-start gap-3">
                <input type="radio" name="choice" value="existing" x-model="choice" class="mt-1">
                <div class="flex-1">
                    <p class="font-semibold text-gray-900"><i class="fa fa-link mr-1.5 text-navy-500"></i>Use the existing vendor as-is</p>
                    <p class="text-sm text-gray-500 mt-0.5">The GSTIN on the bill was a misread — bill against the existing record and ignore it.</p>
                </div>
            </div>
        </label>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">
                Continue to bill <i class="fa fa-arrow-right ml-1"></i>
            </button>
            <a href="{{ route('ai.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</div>
@endsection
