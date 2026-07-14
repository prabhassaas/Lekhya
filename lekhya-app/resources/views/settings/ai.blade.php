@extends('layouts.app')
@section('title', 'AI Settings')
@section('page-title', 'AI / OCR Configuration')

@section('content')
<div class="py-4 max-w-2xl space-y-6">

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
        <i class="fa fa-shield-halved mr-1.5"></i>
        Your API key is <strong>encrypted at rest</strong> in the database, scoped to your organisation only, and never shown again once saved or sent to the browser.
    </div>

    <form method="POST" action="{{ route('settings.ai.update') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
            <select name="provider" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                @foreach($providers as $key => $label)
                <option value="{{ $key }}" @selected(old('provider', $setting->provider ?? 'lekhya') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
            @if($setting->exists && $setting->hasKey())
            <div class="flex items-center gap-2 mb-2 text-sm text-green-700">
                <i class="fa fa-circle-check"></i>
                <span>A key is configured ({{ $setting->maskedKey() }}). Leave blank to keep it.</span>
            </div>
            @endif
            <input type="password" name="api_key" autocomplete="off"
                   placeholder="{{ $setting->exists && $setting->hasKey() ? 'Enter a new key to replace…' : 'Paste your AI engine API key' }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
            <p class="text-xs text-gray-400 mt-1">Optional — Lekhya AI is already enabled on your plan. Add your own key only if you want to use it instead.</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Text Model</label>
                <input type="text" name="text_model" value="{{ old('text_model', $setting->text_model) }}" placeholder="{{ $defaults['text'] }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vision Model (OCR)</label>
                <input type="text" name="vision_model" value="{{ old('vision_model', $setting->vision_model) }}" placeholder="{{ $defaults['vision'] }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
            </div>
        </div>
        <p class="text-xs text-gray-400 -mt-2">Leave blank to use the recommended defaults shown as placeholders.</p>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $setting->is_active ?? true)) class="rounded border-gray-300">
            Enable AI features (OCR, suggestions, NL queries)
        </label>

        <div class="flex items-center gap-3 pt-2 border-t border-gray-50">
            <button type="submit" class="px-5 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">Save Settings</button>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 flex items-center justify-between">
        <div>
            <p class="font-medium text-gray-900 text-sm">Test connection</p>
            <p class="text-xs text-gray-500 mt-0.5">
                @if($setting->last_tested_at)
                    Last tested {{ $setting->last_tested_at->diffForHumans() }} —
                    <span class="{{ $setting->last_test_status === 'ok' ? 'text-green-600' : 'text-red-600' }} font-medium">{{ $setting->last_test_status === 'ok' ? 'OK' : 'Failed' }}</span>
                @else
                    Runs a quick round-trip through the configured provider.
                @endif
            </p>
        </div>
        <form method="POST" action="{{ route('settings.ai.test') }}">
            @csrf
            <button type="submit" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                <i class="fa fa-plug mr-1.5"></i>Test
            </button>
        </form>
    </div>

    <p class="text-xs text-gray-400">
        Until a key is set, AI features run on a built-in mock so you can explore the flow. No data leaves your server without a configured provider.
    </p>
</div>
@endsection
