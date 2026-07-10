@extends('layouts.app')
@section('title', 'Billing')
@section('page-title', 'Billing')

@section('content')
@php
    $entitlement = $tenant?->entitlements()->where('app','lekhya')->where('is_active',true)->first();
@endphp
<div class="py-4 max-w-2xl space-y-6">

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
@endsection
