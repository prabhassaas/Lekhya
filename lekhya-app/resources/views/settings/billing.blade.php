@extends('layouts.app')
@section('title', 'Billing')
@section('page-title', 'Billing')

@section('content')
@php
    $entitlement = $tenant?->entitlements()->where('app','lekhya')->where('is_active',true)->first();
@endphp
<div class="py-4 max-w-3xl">
    @include('settings._nav')
    <div class="max-w-2xl space-y-6">

    {{-- Current plan --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-3">Current Plan</h3>
        @if($entitlement)
        <div class="flex items-center justify-between">
            <div>
                <p class="text-lg font-bold text-navy-700 capitalize">Lekhya {{ $entitlement->edition }} {{ $entitlement->plan ? '· ' . ucfirst($entitlement->plan) : '' }}</p>
                @if($entitlement->trial_ends_at && $entitlement->trial_ends_at->isFuture())
                <p class="text-sm text-amber-600 mt-0.5">Trial — {{ $entitlement->trial_ends_at->diffForHumans() }} remaining</p>
                @else
                <p class="text-sm text-gray-500 mt-0.5">Active</p>
                @endif
            </div>
            <a href="{{ route('marketing.pricing') }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">Change plan</a>
        </div>
        @else
        <p class="text-sm text-gray-500">No active subscription. <a href="{{ route('marketing.pricing') }}" class="text-navy-600 hover:underline">View plans →</a></p>
        @endif
    </div>

    {{-- AI usage --}}
    @php
        $aiUsed = $tenant?->aiCreditsUsed() ?? 0;
        $aiLimit = $tenant?->aiCreditLimit() ?? 0;
        $aiUnlimited = $tenant?->aiCreditsUnlimited() ?? false;
        $aiPct = ($aiUnlimited || $aiLimit <= 0) ? 0 : min(100, round($aiUsed / $aiLimit * 100));
    @endphp
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-900"><i class="fa fa-bolt text-amber-500 mr-1.5"></i>AI Credits</h3>
            <span class="text-sm text-gray-500">
                @if($aiUnlimited) Unlimited @else {{ number_format($aiUsed) }} / {{ number_format($aiLimit) }} used this month @endif
            </span>
        </div>
        @unless($aiUnlimited)
        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-2 rounded-full {{ $aiPct >= 100 ? 'bg-red-500' : ($aiPct >= 80 ? 'bg-amber-500' : 'bg-navy-500') }}" style="width: {{ $aiPct }}%"></div>
        </div>
        <p class="text-xs text-gray-400 mt-2">
            {{ number_format(max(0, $aiLimit - $aiUsed)) }} credits left — resets on the 1st. 1 credit = 1 invoice scan, AI question, or auto-coding.
        </p>
        @endunless
    </div>

    {{-- Invoice email test --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-1">Subscription Invoices</h3>
        <p class="text-sm text-gray-500 mb-4">
            When a payment succeeds, {{ config('prabhas.name') }} emails a GST tax invoice (PDF) to your registered address.
            Send yourself a sample to preview the format and confirm email delivery is working.
        </p>
        <form method="POST" action="{{ route('settings.billing.test') }}">
            @csrf
            <button type="submit" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
                <i class="fa fa-paper-plane mr-1.5"></i>Email me a sample invoice
            </button>
            <span class="ml-2 text-xs text-gray-400">Sends to {{ auth()->user()->email }}</span>
        </form>
    </div>

    <p class="text-xs text-gray-400">
        Razorpay recurring billing is coming in the subscription manager. Invoices already generate and email automatically once a payment is recorded.
    </p>
    </div>
</div>
@endsection
