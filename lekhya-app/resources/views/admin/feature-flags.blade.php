@extends('layouts.admin')

@section('title', 'Feature Flags')
@section('page-title', 'Feature Flags')

@section('content')

<div class="max-w-2xl">
    <p class="text-gray-500 text-sm mb-6">
        Toggle features globally. Changes are stored in
        <code class="text-xs bg-gray-800 text-violet-300 px-1.5 py-0.5 rounded">storage/app/feature_flags.json</code>
        and take effect immediately without a deploy.
    </p>

    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        @foreach($flags as $key => $value)
        <div class="flex items-center justify-between px-5 py-4 {{ ! $loop->last ? 'border-b border-gray-800' : '' }} group">
            <div>
                <p class="text-white text-sm font-medium font-mono">{{ $key }}</p>
                <p class="text-gray-500 text-xs mt-0.5">
                    @switch($key)
                        @case('ai_enabled')           AI assistant across all tenants @break
                        @case('gst_e_invoice')         GST e-Invoice generation (IRN) @break
                        @case('seedha_bill_connector') Seedha Bill import connector @break
                        @case('tally_migration')       Tally XML import tool @break
                        @case('pramaan_ca')            Pramaan CA edition features @break
                        @case('razorpay_billing')      Live Razorpay subscription billing @break
                        @default                       Feature flag @break
                    @endswitch
                </p>
            </div>
            <form method="POST" action="{{ route('admin.feature-flags.toggle') }}">
                @csrf
                <input type="hidden" name="flag" value="{{ $key }}">
                <button type="submit"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none
                               {{ $value ? 'bg-violet-600 hover:bg-violet-700' : 'bg-gray-700 hover:bg-gray-600' }}"
                        onclick="return confirm('Toggle \'{{ $key }}\'?')">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                                 {{ $value ? 'translate-x-6' : 'translate-x-1' }}"></span>
                </button>
            </form>
        </div>
        @endforeach
    </div>

    <p class="mt-4 text-xs text-gray-600">
        Last modified: {{ file_exists(storage_path('app/feature_flags.json'))
            ? date('d M Y H:i', filemtime(storage_path('app/feature_flags.json')))
            : 'Never (defaults in use)' }}
    </p>
</div>

@endsection
