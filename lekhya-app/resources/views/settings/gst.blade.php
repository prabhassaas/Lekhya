@extends('layouts.app')
@section('title', 'GST Filing')
@section('page-title', 'Settings')

@section('content')
<div class="py-4 max-w-2xl">
    @include('settings._nav')

    @if(session('success'))<div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

    {{-- How it works --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-900 mb-5">
        <p class="font-semibold mb-1"><i class="fa fa-circle-info mr-1.5"></i>Connect your company's GST</p>
        <p class="text-blue-800">e-Invoices, e-Way Bills and returns for <b>{{ $tenant->name }}</b> run under <b>your own GSTIN</b>, using API credentials you create on the government portal. Your passwords are <b>encrypted at rest</b> and never shown again once saved.</p>
    </div>

    @unless($entitled)
    {{-- Plan gate --}}
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-center">
        <i class="fa fa-lock text-amber-500 text-2xl mb-2"></i>
        <p class="font-semibold text-amber-900">GST e-invoicing &amp; filing isn't in your current plan</p>
        <p class="text-sm text-amber-800 mt-1 mb-4">Upgrade to connect your GSTIN and file directly from Lekhya.</p>
        <a href="{{ route('settings.billing') }}" class="inline-block px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg">View plans</a>
    </div>
    @else

    {{-- Status --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-5">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $connected ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400' }}">
                    <i class="fa {{ $connected ? 'fa-plug-circle-check' : 'fa-plug' }}"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-900 flex items-center gap-2">
                        {{ $connected ? 'Connected' : 'Not connected' }}
                        @if($connected)<span class="text-[11px] px-2 py-0.5 rounded-full font-medium {{ $setting->isProduction() ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">{{ $setting->isProduction() ? 'Production' : 'Sandbox' }}</span>@endif
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        @if($connected)
                            GSTIN {{ $setting->gstin }}@if($setting->last_verified_at) · verified {{ $setting->last_verified_at->diffForHumans() }}@endif
                        @else
                            Add your GSTIN + credentials below to start filing.
                        @endif
                    </p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-[11px] uppercase tracking-wider text-gray-400">Filings this month</p>
                <p class="text-sm font-semibold text-gray-800">{{ $used }}<span class="text-gray-400 font-normal">/ {{ $unlimited ? '∞' : $limit }}</span></p>
            </div>
        </div>
    </div>

    {{-- Connection form --}}
    <form method="POST" action="{{ route('settings.gst.update') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
        @csrf @method('PUT')

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">GSTIN <span class="text-red-500">*</span></label>
                <input type="text" name="gstin" required maxlength="15" value="{{ old('gstin', $setting->gstin) }}" placeholder="29ABCDE1234F1Z5"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Environment</label>
                <select name="environment" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="sandbox" @selected(old('environment', $setting->environment ?? 'sandbox') === 'sandbox')>Sandbox (testing)</option>
                    <option value="production" @selected(old('environment', $setting->environment) === 'production')>Production (live filing)</option>
                </select>
            </div>
        </div>

        <div class="border-t border-gray-100 pt-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">e-Invoice (IRP) API</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API username</label>
                    <input type="text" name="einvoice_username" autocomplete="off" value="{{ old('einvoice_username', $setting->einvoice_username) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API password</label>
                    @if($setting->hasCredentials('einvoice'))<p class="text-xs text-green-700 mb-1"><i class="fa fa-circle-check mr-1"></i>Set — leave blank to keep</p>@endif
                    <input type="password" name="einvoice_password" autocomplete="new-password"
                           placeholder="{{ $setting->hasCredentials('einvoice') ? 'Enter to replace…' : 'API password' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 pt-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">e-Way Bill API</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API username</label>
                    <input type="text" name="ewb_username" autocomplete="off" value="{{ old('ewb_username', $setting->ewb_username) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API password</label>
                    @if($setting->hasCredentials('ewb'))<p class="text-xs text-green-700 mb-1"><i class="fa fa-circle-check mr-1"></i>Set — leave blank to keep</p>@endif
                    <input type="password" name="ewb_password" autocomplete="new-password"
                           placeholder="{{ $setting->hasCredentials('ewb') ? 'Enter to replace…' : 'API password' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 pt-4 grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Returns portal username <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" name="returns_username" autocomplete="off" value="{{ old('returns_username', $setting->returns_username) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                <p class="text-xs text-gray-400 mt-1">For GSTR filing. You'll authorise each filing with an OTP.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">GSP <span class="text-gray-400 font-normal">(optional override)</span></label>
                <input type="text" name="gsp" value="{{ old('gsp', $setting->gsp) }}" placeholder="use platform default"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-50">
            <button type="submit" class="px-5 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">{{ $connected ? 'Save changes' : 'Connect GST' }}</button>
            <button form="gst-test" type="submit" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"><i class="fa fa-plug mr-1.5"></i>Verify GSTIN</button>
            @if($connected)
            <button form="gst-disconnect" type="submit" onclick="return confirm('Disconnect GST and clear stored credentials?')" class="px-4 py-2 border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 ml-auto"><i class="fa fa-plug-circle-xmark mr-1.5"></i>Disconnect</button>
            @endif
        </div>
    </form>

    {{-- Sibling forms for test / disconnect (kept out of the main form) --}}
    <form id="gst-test" method="POST" action="{{ route('settings.gst.test') }}" class="hidden">@csrf</form>
    <form id="gst-disconnect" method="POST" action="{{ route('settings.gst.disconnect') }}" class="hidden">@csrf @method('DELETE')</form>

    <p class="text-xs text-gray-400 mt-4">
        The platform's GSP contract is shared; your GSTIN and credentials are yours alone. Until your GSP goes live, calls run against a built-in sandbox so you can rehearse the flow safely.
    </p>
    @endunless
</div>
@endsection
