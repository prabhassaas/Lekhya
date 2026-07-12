@extends('layouts.marketing')
@section('title', 'Register — Lekhya')

@push('styles')
<style>
  .step-fade-enter { animation: stepIn 0.28s ease both; }
  @keyframes stepIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/40 to-indigo-50/60 py-10 px-4"
     x-data="registerWizard()"
     x-cloak>

  {{-- Session / validation errors --}}
  @if(session('error') || $errors->any())
  <div class="max-w-2xl mx-auto mb-6">
    <div class="flex gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-800">
      <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <div>
        @if(session('error'))<p class="font-medium">{{ session('error') }}</p>@endif
        @foreach($errors->all() as $e)<p>&bull; {{ $e }}</p>@endforeach
      </div>
    </div>
  </div>
  @endif

  {{-- Branding --}}
  <div class="text-center mb-8">
    <a href="{{ route('marketing.home') }}" class="inline-flex items-center space-x-2 mb-3">
      <div class="w-10 h-10 bg-navy-600 rounded-xl flex items-center justify-center shadow-md">
        <img src="{{ asset('logo-mark.svg') }}" alt="Lekhya" class="w-6 h-6">
      </div>
      <span class="text-navy-600 font-bold text-2xl tracking-tight">Lekhya</span>
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Start your 14-day free trial</h1>
    <p class="text-gray-500 text-sm mt-1">No credit card required &bull; Cancel anytime</p>
  </div>

  {{-- Progress indicator --}}
  <div class="max-w-sm mx-auto mb-8">
    <div class="flex items-center justify-center">

      {{-- Step 1 bubble --}}
      <div class="flex flex-col items-center">
        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shadow-sm"
             :class="currentStep > 1 ? 'bg-green-500 text-white' : currentStep === 1 ? 'bg-navy-600 text-white ring-4 ring-navy-600/20' : 'bg-gray-200 text-gray-400'">
          <template x-if="currentStep > 1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
          </template>
          <template x-if="currentStep <= 1">
            <span>1</span>
          </template>
        </div>
        <span class="text-xs mt-1 font-medium transition-colors duration-300"
              :class="currentStep >= 1 ? 'text-navy-600' : 'text-gray-400'">Company</span>
      </div>

      <div class="h-0.5 w-14 mb-4 transition-all duration-500 mx-1"
           :class="currentStep >= 2 ? 'bg-navy-600' : 'bg-gray-200'"></div>

      {{-- Step 2 bubble --}}
      <div class="flex flex-col items-center">
        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shadow-sm"
             :class="currentStep > 2 ? 'bg-green-500 text-white' : currentStep === 2 ? 'bg-navy-600 text-white ring-4 ring-navy-600/20' : 'bg-gray-200 text-gray-400'">
          <template x-if="currentStep > 2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
          </template>
          <template x-if="currentStep <= 2">
            <span>2</span>
          </template>
        </div>
        <span class="text-xs mt-1 font-medium transition-colors duration-300"
              :class="currentStep >= 2 ? 'text-navy-600' : 'text-gray-400'">Profile</span>
      </div>

      <div class="h-0.5 w-14 mb-4 transition-all duration-500 mx-1"
           :class="currentStep >= 3 ? 'bg-navy-600' : 'bg-gray-200'"></div>

      {{-- Step 3 bubble --}}
      <div class="flex flex-col items-center">
        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shadow-sm"
             :class="currentStep === 3 ? 'bg-navy-600 text-white ring-4 ring-navy-600/20' : 'bg-gray-200 text-gray-400'">
          <span>3</span>
        </div>
        <span class="text-xs mt-1 font-medium transition-colors duration-300"
              :class="currentStep >= 3 ? 'text-navy-600' : 'text-gray-400'">Plan</span>
      </div>

    </div>
  </div>

  {{-- The actual form — hidden inputs carry values; Alpine drives display --}}
  <form method="POST" action="{{ route('register') }}" x-ref="form" @submit.prevent="submitForm()">
    @csrf
    <input type="hidden" name="company_name"       :value="form.company_name">
    <input type="hidden" name="gstin"              :value="form.gstin">
    <input type="hidden" name="state_code"         :value="form.state_code">
    <input type="hidden" name="business_type"      :value="form.business_type">
    <input type="hidden" name="fiscal_year_start"  :value="form.fiscal_year_start">
    <input type="hidden" name="name"               :value="form.name">
    <input type="hidden" name="designation"        :value="form.designation">
    <input type="hidden" name="phone"              :value="form.phone">
    <input type="hidden" name="email"              :value="form.email">
    <input type="hidden" name="password"           :value="form.password">
    <input type="hidden" name="password_confirmation" :value="form.password_confirmation">
    <input type="hidden" name="plan"               :value="form.plan">

    {{-- ═══════════════════════════════════════════
         STEP 1 — Company
    ═══════════════════════════════════════════ --}}
    <div x-show="currentStep === 1"
         x-transition:enter="transition ease-out duration-280"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-180"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-3"
         class="max-w-lg mx-auto">

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="mb-6">
          <h2 class="text-xl font-bold text-gray-900">Your Company</h2>
          <p class="text-sm text-gray-500 mt-0.5">Tell us about your business</p>
        </div>

        {{-- Company Name --}}
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Company Name <span class="text-red-500">*</span>
          </label>
          <input type="text"
                 x-model="form.company_name"
                 :class="errors.company_name ? 'border-red-400 focus:ring-red-400' : 'border-gray-300 focus:ring-navy-600'"
                 class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                 placeholder="Acme Traders Pvt Ltd"
                 autocomplete="organization">
          <p x-show="errors.company_name" x-text="errors.company_name"
             class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
          </p>
        </div>

        {{-- GSTIN --}}
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            GSTIN <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="text"
                   x-model="form.gstin"
                   @input.debounce.700ms="validateGstin()"
                   :class="errors.gstin ? 'border-red-400 focus:ring-red-400' : 'border-gray-300 focus:ring-navy-600'"
                   class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition uppercase pr-11 tracking-widest font-mono"
                   placeholder="29ABCDE1234F1Z5"
                   maxlength="15"
                   autocomplete="off"
                   spellcheck="false">
            {{-- Spinner --}}
            <div x-show="gstin.loading" class="absolute right-3.5 top-2.5 pointer-events-none">
              <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
            </div>
            {{-- Valid --}}
            <div x-show="gstin.status === 'valid' && !gstin.loading" class="absolute right-3.5 top-2.5 pointer-events-none">
              <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            {{-- Invalid --}}
            <div x-show="gstin.status === 'invalid' && !gstin.loading" class="absolute right-3.5 top-2.5 pointer-events-none">
              <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </div>
          </div>
          <p x-show="gstin.status === 'valid'"
             class="text-green-600 text-xs mt-1.5 font-medium" x-text="'✓ ' + gstin.message"></p>
          <p x-show="gstin.status === 'invalid'"
             class="text-red-500 text-xs mt-1.5" x-text="gstin.message"></p>
          <p x-show="errors.gstin" x-text="errors.gstin"
             class="text-red-500 text-xs mt-1.5"></p>
        </div>

        {{-- State --}}
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            State <span class="text-red-500">*</span>
            <span x-show="gstin.status === 'valid'" class="text-green-600 font-normal ml-1">(auto-filled from GSTIN)</span>
          </label>
          <select x-model="form.state_code"
                  :class="errors.state_code ? 'border-red-400 focus:ring-red-400' : 'border-gray-300 focus:ring-navy-600'"
                  class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition bg-white">
            <option value="">Select state…</option>
            <template x-for="s in states" :key="s.code">
              <option :value="s.code" x-text="s.code + ' — ' + s.name"></option>
            </template>
          </select>
          <p x-show="errors.state_code" x-text="errors.state_code"
             class="text-red-500 text-xs mt-1.5"></p>
        </div>

        {{-- Business Type --}}
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Business Type <span class="text-red-500">*</span>
          </label>
          <select x-model="form.business_type"
                  :class="errors.business_type ? 'border-red-400 focus:ring-red-400' : 'border-gray-300 focus:ring-navy-600'"
                  class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition bg-white">
            <option value="">Select type…</option>
            <option value="Proprietorship">Proprietorship</option>
            <option value="Private Limited">Private Limited</option>
            <option value="LLP">LLP</option>
            <option value="Partnership">Partnership</option>
            <option value="HUF">HUF</option>
            <option value="Trust/NGO">Trust / NGO</option>
          </select>
          <p x-show="errors.business_type" x-text="errors.business_type"
             class="text-red-500 text-xs mt-1.5"></p>
        </div>

        {{-- Fiscal Year Start --}}
        <div class="mb-7">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Fiscal Year Start <span class="text-red-500">*</span>
          </label>
          <select x-model="form.fiscal_year_start"
                  class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-navy-600 focus:border-transparent transition bg-white">
            <option value="April">April — standard India FY (April–March)</option>
            <option value="January">January (Jan–Dec)</option>
            <option value="July">July (Jul–Jun)</option>
          </select>
        </div>

        <button type="button" @click="nextStep()"
                class="w-full bg-navy-600 hover:bg-navy-700 text-white font-semibold py-3 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-sm">
          Continue
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      </div>
    </div>

    {{-- ═══════════════════════════════════════════
         STEP 2 — Profile
    ═══════════════════════════════════════════ --}}
    <div x-show="currentStep === 2"
         x-transition:enter="transition ease-out duration-280"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-180"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-3"
         class="max-w-lg mx-auto">

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="mb-6">
          <h2 class="text-xl font-bold text-gray-900">Your Profile</h2>
          <p class="text-sm text-gray-500 mt-0.5">Who's setting up the account?</p>
        </div>

        {{-- Full Name --}}
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Full Name <span class="text-red-500">*</span>
          </label>
          <input type="text"
                 x-model="form.name"
                 :class="errors.name ? 'border-red-400 focus:ring-red-400' : 'border-gray-300 focus:ring-navy-600'"
                 class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                 placeholder="Rajesh Kumar"
                 autocomplete="name">
          <p x-show="errors.name" x-text="errors.name" class="text-red-500 text-xs mt-1.5"></p>
        </div>

        {{-- Designation --}}
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Designation</label>
          <select x-model="form.designation"
                  class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-navy-600 focus:border-transparent transition bg-white">
            <option value="">Select designation…</option>
            <option value="Owner/Founder">Owner / Founder</option>
            <option value="Director">Director</option>
            <option value="Partner">Partner</option>
            <option value="CA">CA (Chartered Accountant)</option>
            <option value="CFO">CFO</option>
            <option value="Accountant">Accountant</option>
            <option value="Manager">Manager</option>
            <option value="Other">Other</option>
          </select>
        </div>

        {{-- Mobile --}}
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Mobile <span class="text-red-500">*</span>
          </label>
          <div class="flex">
            <span class="inline-flex items-center px-3.5 text-sm font-semibold text-gray-600 bg-gray-50 border border-r-0 border-gray-300 rounded-l-xl select-none">
              +91
            </span>
            <input type="tel"
                   x-model="form.phone"
                   :class="errors.phone ? 'border-red-400 focus:ring-red-400' : 'border-gray-300 focus:ring-navy-600'"
                   class="flex-1 border rounded-r-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                   placeholder="98765 43210"
                   maxlength="10"
                   autocomplete="tel">
          </div>
          <p class="text-gray-400 text-xs mt-1.5 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
            </svg>
            WhatsApp-enabled — we'll send trial updates here
          </p>
          <p x-show="errors.phone" x-text="errors.phone" class="text-red-500 text-xs mt-1"></p>
        </div>

        {{-- Email --}}
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Work Email <span class="text-red-500">*</span>
          </label>
          <input type="email"
                 x-model="form.email"
                 :class="errors.email ? 'border-red-400 focus:ring-red-400' : 'border-gray-300 focus:ring-navy-600'"
                 class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                 placeholder="you@company.com"
                 autocomplete="email">
          <p x-show="errors.email" x-text="errors.email" class="text-red-500 text-xs mt-1.5"></p>
        </div>

        {{-- Password --}}
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Password <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input :type="showPassword ? 'text' : 'password'"
                   x-model="form.password"
                   @input="scorePassword()"
                   :class="errors.password ? 'border-red-400 focus:ring-red-400' : 'border-gray-300 focus:ring-navy-600'"
                   class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition pr-11"
                   placeholder="Minimum 8 characters"
                   autocomplete="new-password">
            <button type="button" @click="showPassword = !showPassword"
                    class="absolute right-3.5 top-2.5 text-gray-400 hover:text-gray-600 transition-colors">
              <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
              </svg>
            </button>
          </div>
          {{-- Strength meter --}}
          <div x-show="form.password.length > 0" class="mt-2.5">
            <div class="flex gap-1 mb-1.5">
              <div class="h-1.5 flex-1 rounded-full transition-all duration-300"
                   :class="pwScore >= 1 ? pwColor : 'bg-gray-200'"></div>
              <div class="h-1.5 flex-1 rounded-full transition-all duration-300"
                   :class="pwScore >= 2 ? pwColor : 'bg-gray-200'"></div>
              <div class="h-1.5 flex-1 rounded-full transition-all duration-300"
                   :class="pwScore >= 3 ? pwColor : 'bg-gray-200'"></div>
              <div class="h-1.5 flex-1 rounded-full transition-all duration-300"
                   :class="pwScore >= 4 ? pwColor : 'bg-gray-200'"></div>
            </div>
            <p class="text-xs font-medium transition-colors duration-300"
               :class="{
                 'text-red-500':    pwScore === 1,
                 'text-orange-500': pwScore === 2,
                 'text-yellow-600': pwScore === 3,
                 'text-green-600':  pwScore === 4,
               }"
               x-text="pwLabel"></p>
          </div>
          <p x-show="errors.password" x-text="errors.password" class="text-red-500 text-xs mt-1.5"></p>
        </div>

        {{-- Confirm Password --}}
        <div class="mb-7">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Confirm Password <span class="text-red-500">*</span>
          </label>
          <input type="password"
                 x-model="form.password_confirmation"
                 :class="(form.password_confirmation && form.password !== form.password_confirmation) ? 'border-red-400 focus:ring-red-400' : 'border-gray-300 focus:ring-navy-600'"
                 class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                 placeholder="Repeat your password"
                 autocomplete="new-password">
          <p x-show="form.password_confirmation.length > 0 && form.password !== form.password_confirmation"
             class="text-red-500 text-xs mt-1.5">Passwords do not match</p>
          <p x-show="form.password_confirmation.length > 0 && form.password === form.password_confirmation && form.password.length >= 8"
             class="text-green-600 text-xs mt-1.5 font-medium">Passwords match</p>
          <p x-show="errors.password_confirmation" x-text="errors.password_confirmation"
             class="text-red-500 text-xs mt-1.5"></p>
        </div>

        <div class="flex gap-3">
          <button type="button" @click="prevStep()"
                  class="flex-1 border border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold py-3 rounded-xl transition flex items-center justify-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back
          </button>
          <button type="button" @click="nextStep()"
                  class="flex-[2] bg-navy-600 hover:bg-navy-700 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2 shadow-sm">
            Choose Plan
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    {{-- ═══════════════════════════════════════════
         STEP 3 — Plan
    ═══════════════════════════════════════════ --}}
    <div x-show="currentStep === 3"
         x-transition:enter="transition ease-out duration-280"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-180"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-3"
         class="max-w-5xl mx-auto">

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="text-center mb-8">
          <h2 class="text-xl font-bold text-gray-900">Choose Your Plan</h2>
          <p class="text-sm text-gray-500 mt-1">All plans start with a <span class="font-semibold text-navy-600">14-day free trial</span> — no credit card needed</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

          {{-- ── Lite ── --}}
          <div @click="form.plan = 'lite'"
               :class="form.plan === 'lite'
                 ? 'border-navy-600 ring-2 ring-navy-600 ring-offset-1 bg-blue-50/60'
                 : 'border-gray-200 hover:border-navy-400 hover:shadow-md'"
               class="relative border-2 rounded-2xl p-6 cursor-pointer transition-all duration-200 select-none">

            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Lite</span>
              <div x-show="form.plan === 'lite'"
                   class="w-5 h-5 bg-navy-600 rounded-full flex items-center justify-center">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
            </div>

            <div class="mb-5">
              <div class="flex items-baseline gap-1">
                <span class="text-3xl font-bold text-gray-900">₹499</span>
                <span class="text-sm text-gray-400 font-medium">/month</span>
              </div>
              <p class="text-xs text-gray-400 mt-0.5">Billed monthly, cancel anytime</p>
            </div>

            <ul class="space-y-2.5 text-sm mb-6">
              <li class="flex items-center gap-2 text-gray-700">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                Double-entry accounting
              </li>
              <li class="flex items-center gap-2 text-gray-700">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                GST invoicing &amp; returns
              </li>
              <li class="flex items-center gap-2 text-gray-700">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                GSTR-1 &amp; GSTR-3B
              </li>
              <li class="flex items-center gap-2 text-gray-700">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                1 user &bull; 1 company
              </li>
              <li class="flex items-center gap-2 text-gray-300">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                AI features
              </li>
              <li class="flex items-center gap-2 text-gray-300">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Seedha Bill connector
              </li>
            </ul>

            <div class="w-full py-2.5 rounded-xl text-sm font-semibold text-center transition-all duration-200"
                 :class="form.plan === 'lite'
                   ? 'bg-navy-600 text-white'
                   : 'border border-navy-600 text-navy-600 hover:bg-navy-50'">
              <span x-text="form.plan === 'lite' ? '✓ Selected' : 'Start Free Trial'"></span>
            </div>
          </div>

          {{-- ── Pro (RECOMMENDED) ── --}}
          <div @click="form.plan = 'pro'"
               :class="form.plan === 'pro'
                 ? 'ring-4 ring-white/30'
                 : 'opacity-95 hover:opacity-100 hover:shadow-2xl'"
               class="relative border-2 border-navy-600 rounded-2xl p-6 cursor-pointer transition-all duration-200 bg-navy-600 text-white select-none shadow-lg">

            <div class="absolute -top-3.5 left-0 right-0 flex justify-center">
              <span class="bg-amber-400 text-amber-900 text-xs font-bold px-4 py-1 rounded-full uppercase tracking-wider shadow-sm">
                Recommended
              </span>
            </div>

            <div class="flex items-center justify-between mb-3 mt-1">
              <span class="text-xs font-bold text-blue-200 uppercase tracking-widest">Pro</span>
              <div x-show="form.plan === 'pro'"
                   class="w-5 h-5 bg-white rounded-full flex items-center justify-center">
                <svg class="w-3 h-3 text-navy-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
            </div>

            <div class="mb-5">
              <div class="flex items-baseline gap-1">
                <span class="text-3xl font-bold text-white">₹1,299</span>
                <span class="text-sm text-blue-200 font-medium">/month</span>
              </div>
              <p class="text-xs text-blue-200/70 mt-0.5">Billed monthly, cancel anytime</p>
            </div>

            <ul class="space-y-2.5 text-sm mb-6">
              <li class="flex items-center gap-2 text-blue-100">
                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                Everything in Lite
              </li>
              <li class="flex items-center gap-2 text-blue-100">
                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                AI auto-coding &amp; OCR
              </li>
              <li class="flex items-center gap-2 text-blue-100">
                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                Seedha Bill connector
              </li>
              <li class="flex items-center gap-2 text-blue-100">
                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                2B reconciliation
              </li>
              <li class="flex items-center gap-2 text-blue-100">
                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                5 users &bull; 1 company
              </li>
              <li class="flex items-center gap-2 text-blue-100">
                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                Priority support
              </li>
            </ul>

            <div class="w-full py-2.5 rounded-xl text-sm font-semibold text-center transition-all duration-200"
                 :class="form.plan === 'pro'
                   ? 'bg-white text-navy-600'
                   : 'bg-white/15 text-white hover:bg-white/25 border border-white/30'">
              <span x-text="form.plan === 'pro' ? '✓ Selected' : 'Start Free Trial'"></span>
            </div>
          </div>

          {{-- ── Lifetime ── --}}
          <div @click="form.plan = 'lifetime'"
               :class="form.plan === 'lifetime'
                 ? 'border-amber-500 ring-2 ring-amber-500 ring-offset-1 bg-amber-50/60'
                 : 'border-gray-200 hover:border-amber-400 hover:shadow-md'"
               class="relative border-2 rounded-2xl p-6 cursor-pointer transition-all duration-200 select-none">

            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">Lifetime</span>
              <div x-show="form.plan === 'lifetime'"
                   class="w-5 h-5 bg-amber-500 rounded-full flex items-center justify-center">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
            </div>

            <div class="mb-5">
              <div class="flex items-baseline gap-1">
                <span class="text-3xl font-bold text-gray-900">₹24,999</span>
                <span class="text-sm text-gray-400 font-medium">one-time</span>
              </div>
              <p class="text-xs text-green-600 font-semibold mt-0.5">Pay once &bull; use forever</p>
            </div>

            <ul class="space-y-2.5 text-sm mb-6">
              <li class="flex items-center gap-2 text-gray-700">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                Everything in Pro
              </li>
              <li class="flex items-center gap-2 text-gray-700">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                3 companies
              </li>
              <li class="flex items-center gap-2 text-gray-700">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                10 users
              </li>
              <li class="flex items-center gap-2 text-gray-700">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                All future updates free
              </li>
              <li class="flex items-center gap-2 text-gray-700">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                Dedicated support
              </li>
              <li class="flex items-center gap-2 text-gray-700">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                No recurring fees
              </li>
            </ul>

            <div class="w-full py-2.5 rounded-xl text-sm font-semibold text-center transition-all duration-200"
                 :class="form.plan === 'lifetime'
                   ? 'bg-amber-500 text-white'
                   : 'border border-amber-500 text-amber-600 hover:bg-amber-50'">
              <span x-text="form.plan === 'lifetime' ? '✓ Selected' : 'Buy Lifetime'"></span>
            </div>
          </div>

        </div>

        <p class="text-center text-gray-400 text-xs mb-6">
          All plans start with a 14-day free trial. You won't be charged until the trial ends.
        </p>

        <div class="flex gap-3">
          <button type="button" @click="prevStep()"
                  class="flex-1 border border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold py-3 rounded-xl transition flex items-center justify-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back
          </button>

          <button type="submit"
                  :disabled="submitting"
                  class="flex-[2] bg-navy-600 hover:bg-navy-700 disabled:opacity-60 disabled:cursor-not-allowed text-white font-bold py-3 rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm">
            <svg x-show="submitting" class="animate-spin w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span x-text="submitting
              ? 'Creating your account…'
              : (form.plan === 'lifetime' ? 'Buy Lifetime Access' : 'Start 14-Day Free Trial')">
            </span>
          </button>
        </div>

        <p class="text-center text-xs text-gray-400 mt-4">
          By creating an account you agree to our
          <a href="#" class="underline hover:text-gray-600">Terms of Service</a>
          and
          <a href="#" class="underline hover:text-gray-600">Privacy Policy</a>.
        </p>
      </div>

    </div>{{-- /step 3 --}}

  </form>

  <p class="text-center text-sm text-gray-400 mt-6 pb-10">
    Already have an account?
    <a href="{{ route('login') }}" class="text-navy-600 font-semibold hover:underline">Sign in</a>
  </p>

</div>
@endsection

@push('scripts')
<script>
function registerWizard() {
  return {
    currentStep: {{ $errors->any() ? 1 : 1 }},
    submitting:  false,
    showPassword: false,

    // Password strength
    pwScore: 0,
    pwLabel: '',
    pwColor: 'bg-gray-200',

    // GSTIN validation state
    gstin: {
      loading: false,
      status:  null,   // null | 'valid' | 'invalid'
      message: '',
    },

    // Step-level validation errors (client-side only)
    errors: {},

    // Reactive form model
    form: {
      company_name:       @json(old('company_name', '')),
      gstin:              @json(old('gstin', '')),
      state_code:         @json(old('state_code', '')),
      business_type:      @json(old('business_type', '')),
      fiscal_year_start:  @json(old('fiscal_year_start', 'April')),
      name:               @json(old('name', '')),
      designation:        @json(old('designation', '')),
      phone:              @json(old('phone', '')),
      email:              @json(old('email', '')),
      password:           '',
      password_confirmation: '',
      plan:               @json(old('plan', 'pro')),
    },

    // 37 Indian states / UTs with GST state codes
    states: [
      { code: '01', name: 'Jammu & Kashmir' },
      { code: '02', name: 'Himachal Pradesh' },
      { code: '03', name: 'Punjab' },
      { code: '04', name: 'Chandigarh' },
      { code: '05', name: 'Uttarakhand' },
      { code: '06', name: 'Haryana' },
      { code: '07', name: 'Delhi' },
      { code: '08', name: 'Rajasthan' },
      { code: '09', name: 'Uttar Pradesh' },
      { code: '10', name: 'Bihar' },
      { code: '11', name: 'Sikkim' },
      { code: '12', name: 'Arunachal Pradesh' },
      { code: '13', name: 'Nagaland' },
      { code: '14', name: 'Manipur' },
      { code: '15', name: 'Mizoram' },
      { code: '16', name: 'Tripura' },
      { code: '17', name: 'Meghalaya' },
      { code: '18', name: 'Assam' },
      { code: '19', name: 'West Bengal' },
      { code: '20', name: 'Jharkhand' },
      { code: '21', name: 'Odisha' },
      { code: '22', name: 'Chhattisgarh' },
      { code: '23', name: 'Madhya Pradesh' },
      { code: '24', name: 'Gujarat' },
      { code: '25', name: 'Daman & Diu' },
      { code: '26', name: 'Dadra & Nagar Haveli' },
      { code: '27', name: 'Maharashtra' },
      { code: '28', name: 'Andhra Pradesh (Legacy)' },
      { code: '29', name: 'Karnataka' },
      { code: '30', name: 'Goa' },
      { code: '31', name: 'Lakshadweep' },
      { code: '32', name: 'Kerala' },
      { code: '33', name: 'Tamil Nadu' },
      { code: '34', name: 'Puducherry' },
      { code: '35', name: 'Andaman & Nicobar Islands' },
      { code: '36', name: 'Telangana' },
      { code: '37', name: 'Andhra Pradesh' },
      { code: '38', name: 'Ladakh' },
    ],

    // ── GSTIN live validation ──────────────────────────────────────
    async validateGstin() {
      const raw = (this.form.gstin || '').trim().toUpperCase();
      this.form.gstin = raw; // force uppercase in model
      if (raw.length < 15) {
        this.gstin.status  = null;
        this.gstin.message = '';
        return;
      }
      this.gstin.loading = true;
      this.gstin.status  = null;
      try {
        const res  = await fetch('{{ route('gstin.verify') }}?gstin=' + encodeURIComponent(raw));
        const data = await res.json();
        if (data.valid) {
          this.gstin.status  = 'valid';
          this.gstin.message = data.legal_name || 'Valid GSTIN';
          // Auto-fill company name only if still empty
          if (data.legal_name && !this.form.company_name.trim()) {
            this.form.company_name = data.legal_name;
          }
          // Auto-fill state from first 2 digits of GSTIN
          if (data.state_code) {
            this.form.state_code = String(data.state_code).padStart(2, '0');
          } else {
            // derive from GSTIN prefix
            const prefix = raw.substring(0, 2);
            if (prefix && !isNaN(parseInt(prefix))) {
              this.form.state_code = prefix.padStart(2, '0');
            }
          }
        } else {
          this.gstin.status  = 'invalid';
          this.gstin.message = data.message || 'Invalid GSTIN — check and try again';
        }
      } catch (_) {
        this.gstin.status  = 'invalid';
        this.gstin.message = 'Could not reach validation service';
      } finally {
        this.gstin.loading = false;
      }
    },

    // ── Password strength ──────────────────────────────────────────
    scorePassword() {
      const p = this.form.password;
      let s   = 0;
      if (p.length >= 8)                              s++;
      if (p.length >= 12)                             s++;
      if (/[A-Z]/.test(p) && /[a-z]/.test(p))        s++;
      if (/[0-9]/.test(p))                            s++;
      if (/[^A-Za-z0-9]/.test(p))                    s++;
      this.pwScore = Math.min(4, s);
      const labels = ['', 'Weak',      'Fair',       'Good',        'Strong'];
      const colors = ['', 'bg-red-500','bg-orange-400','bg-yellow-400','bg-green-500'];
      this.pwLabel = labels[this.pwScore] || '';
      this.pwColor = colors[this.pwScore] || 'bg-gray-200';
    },

    // ── Step validations ───────────────────────────────────────────
    validateStep1() {
      const e = {};
      if (!this.form.company_name.trim())   e.company_name  = 'Company name is required';
      if (!this.form.gstin.trim())          e.gstin         = 'GSTIN is required';
      if (!this.form.state_code)            e.state_code    = 'Please select your state';
      if (!this.form.business_type)         e.business_type = 'Please select a business type';
      this.errors = e;
      return Object.keys(e).length === 0;
    },

    validateStep2() {
      const e = {};
      if (!this.form.name.trim())
        e.name = 'Full name is required';
      if (!this.form.phone.trim())
        e.phone = 'Mobile number is required';
      else if (!/^\d{10}$/.test(this.form.phone.trim()))
        e.phone = 'Enter a valid 10-digit mobile number (digits only)';
      if (!this.form.email.trim())
        e.email = 'Email address is required';
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email.trim()))
        e.email = 'Enter a valid email address';
      if (!this.form.password)
        e.password = 'Password is required';
      else if (this.form.password.length < 8)
        e.password = 'Password must be at least 8 characters';
      if (this.form.password !== this.form.password_confirmation)
        e.password_confirmation = 'Passwords do not match';
      this.errors = e;
      return Object.keys(e).length === 0;
    },

    // ── Navigation ─────────────────────────────────────────────────
    nextStep() {
      if (this.currentStep === 1 && !this.validateStep1()) return;
      if (this.currentStep === 2 && !this.validateStep2()) return;
      if (this.currentStep < 3) {
        this.errors = {};
        this.currentStep++;
        this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
      }
    },

    prevStep() {
      this.errors = {};
      if (this.currentStep > 1) {
        this.currentStep--;
        this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
      }
    },

    // ── Submit ─────────────────────────────────────────────────────
    submitForm() {
      if (!this.form.plan) {
        alert('Please select a plan to continue.');
        return;
      }
      this.submitting = true;
      this.$refs.form.submit();
    },
  };
}
</script>
@endpush
