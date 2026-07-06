@extends('layouts.marketing')
@section('title', 'Connect Seedha Bill to Lekhya')
@section('meta-desc', 'Automatically sync your Seedha Bill invoices into Lekhya for accounting. No copy-paste, no re-entry. Two simple ways to connect.')

@section('content')

{{-- Hero --}}
<section class="bg-gradient-to-br from-green-700 to-navy-900 text-white py-20">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <div class="inline-flex items-center px-3 py-1 bg-green-500 bg-opacity-20 border border-green-400 border-opacity-30 rounded-full text-green-200 text-sm mb-6">
      <i class="fa fa-link mr-2"></i> Seedha Bill ↔ Lekhya
    </div>
    <h1 class="text-4xl sm:text-5xl font-bold mb-6">Your invoices, straight into your books</h1>
    <p class="text-xl text-gray-300 max-w-2xl mx-auto">
      When you raise an invoice in Seedha Bill, it automatically appears in Lekhya — ready to post to your ledger. No copy-paste. No re-entry. No mistakes.
    </p>
  </div>
</section>

{{-- Which mode are you? --}}
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

  <div class="text-center mb-12">
    <h2 class="text-2xl font-bold text-gray-900 mb-3">Which situation fits you?</h2>
    <p class="text-gray-500">There are two ways to connect — pick the one that matches how you work.</p>
  </div>

  <div class="grid md:grid-cols-2 gap-8 mb-16">

    {{-- Mode A --}}
    <div class="bg-green-50 border-2 border-green-400 rounded-2xl p-8 relative">
      <div class="absolute -top-4 left-8">
        <span class="bg-green-600 text-white text-sm font-bold px-4 py-1 rounded-full">Option A · Easiest</span>
      </div>
      <div class="mt-2 mb-6">
        <div class="flex items-center space-x-3 mb-3">
          <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
            <i class="fa fa-user text-green-700 text-lg"></i>
          </div>
          <div>
            <h3 class="text-xl font-bold text-gray-900">You use both apps</h3>
            <p class="text-sm text-green-700 font-medium">Same Prabhas account</p>
          </div>
        </div>
        <p class="text-gray-600 leading-relaxed">You raise invoices in Seedha Bill <strong>and</strong> handle your own accounting in Lekhya — both under the same login.</p>
      </div>

      <div class="bg-white rounded-xl p-5 mb-6">
        <p class="text-sm font-semibold text-gray-700 mb-3">How to set it up:</p>
        <ol class="space-y-3 text-sm text-gray-600">
          <li class="flex items-start space-x-3">
            <span class="w-5 h-5 bg-green-600 text-white rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">1</span>
            <span>Open Seedha Bill and go to <strong>Settings → Integrations</strong></span>
          </li>
          <li class="flex items-start space-x-3">
            <span class="w-5 h-5 bg-green-600 text-white rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">2</span>
            <span>Toggle <strong>"Sync to Lekhya"</strong> → On</span>
          </li>
          <li class="flex items-start space-x-3">
            <span class="w-5 h-5 bg-green-600 text-white rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">3</span>
            <span>Done. Future invoices will appear in Lekhya within minutes.</span>
          </li>
        </ol>
      </div>

      <div class="space-y-2 text-sm text-green-800">
        <div class="flex items-center space-x-2"><i class="fa fa-check text-green-600 w-4"></i><span>No tokens or passwords to share</span></div>
        <div class="flex items-center space-x-2"><i class="fa fa-check text-green-600 w-4"></i><span>Bundle discount on your subscription</span></div>
        <div class="flex items-center space-x-2"><i class="fa fa-check text-green-600 w-4"></i><span>Works instantly after one toggle</span></div>
      </div>
    </div>

    {{-- Mode B --}}
    <div class="bg-purple-50 border-2 border-purple-300 rounded-2xl p-8 relative">
      <div class="absolute -top-4 left-8">
        <span class="bg-purple-600 text-white text-sm font-bold px-4 py-1 rounded-full">Option B · For CAs & Accountants</span>
      </div>
      <div class="mt-2 mb-6">
        <div class="flex items-center space-x-3 mb-3">
          <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
            <i class="fa fa-people-arrows text-purple-700 text-lg"></i>
          </div>
          <div>
            <h3 class="text-xl font-bold text-gray-900">Your client uses Seedha Bill</h3>
            <p class="text-sm text-purple-700 font-medium">Different accounts, securely connected</p>
          </div>
        </div>
        <p class="text-gray-600 leading-relaxed">Your client raises invoices in their Seedha Bill account. You handle their accounting in your Lekhya account. A <strong>connection code</strong> links the two — safely.</p>
      </div>

      <div class="bg-white rounded-xl p-5 mb-6">
        <p class="text-sm font-semibold text-gray-700 mb-3">How to set it up:</p>
        <ol class="space-y-3 text-sm text-gray-600">
          <li class="flex items-start space-x-3">
            <span class="w-5 h-5 bg-purple-600 text-white rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">1</span>
            <span>In Lekhya, go to <strong>Connector → Generate Connection Code</strong></span>
          </li>
          <li class="flex items-start space-x-3">
            <span class="w-5 h-5 bg-purple-600 text-white rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">2</span>
            <span>Copy the code and send it to your client (WhatsApp, email — any way)</span>
          </li>
          <li class="flex items-start space-x-3">
            <span class="w-5 h-5 bg-purple-600 text-white rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">3</span>
            <span>Your client pastes it in <strong>Seedha Bill → Settings → Share with Accountant</strong></span>
          </li>
          <li class="flex items-start space-x-3">
            <span class="w-5 h-5 bg-purple-600 text-white rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">4</span>
            <span>Their invoices now flow into your Lekhya automatically</span>
          </li>
        </ol>
      </div>

      <div class="space-y-2 text-sm text-purple-800">
        <div class="flex items-center space-x-2"><i class="fa fa-check text-purple-600 w-4"></i><span>Your client stays in control — they can disconnect any time</span></div>
        <div class="flex items-center space-x-2"><i class="fa fa-check text-purple-600 w-4"></i><span>Each connected client uses 1 client seat on your plan</span></div>
        <div class="flex items-center space-x-2"><i class="fa fa-check text-purple-600 w-4"></i><span>Full audit trail of every invoice synced</span></div>
      </div>
    </div>
  </div>

  {{-- How it looks inside Lekhya --}}
  <div class="bg-gray-50 border border-gray-200 rounded-2xl p-8 mb-16">
    <h2 class="text-xl font-bold text-gray-900 mb-2">What happens when an invoice syncs?</h2>
    <p class="text-gray-500 text-sm mb-8">You don't have to do anything. Here's the flow:</p>
    <div class="grid sm:grid-cols-4 gap-4">
      @foreach([
        ['fa-file-invoice','1. Invoice raised','Your client raises an invoice in Seedha Bill'],
        ['fa-bolt','2. Automatic sync','Within minutes it appears in Lekhya\'s Connector queue'],
        ['fa-eye','3. You review','You see the invoice details — amounts, GST, party name'],
        ['fa-check-circle','4. You approve','One click posts it to your ledger with proper debit/credit entries'],
      ] as [$icon,$step,$desc])
      <div class="text-center">
        <div class="w-14 h-14 bg-white border-2 border-green-400 rounded-full flex items-center justify-center mx-auto mb-3">
          <i class="fa {{ $icon }} text-green-600 text-lg"></i>
        </div>
        <p class="font-semibold text-gray-900 text-sm mb-1">{{ $step }}</p>
        <p class="text-xs text-gray-500">{{ $desc }}</p>
      </div>
      @endforeach
    </div>
  </div>

  {{-- Subscription check --}}
  @auth
    @php $entitlement = auth()->user()->tenant?->entitlements()->where('app','lekhya')->where('is_active',true)->first(); @endphp
    <div class="bg-navy-50 border border-navy-200 rounded-2xl p-6 mb-16">
      <div class="flex items-start space-x-4">
        <div class="w-10 h-10 bg-navy-600 rounded-xl flex items-center justify-center flex-shrink-0">
          <i class="fa fa-user text-white"></i>
        </div>
        <div class="flex-1">
          <p class="font-semibold text-navy-900">You're signed in as {{ auth()->user()->name }}</p>
          @if($entitlement)
            @if($entitlement->plan?->slug === 'solo' || $entitlement->plan?->name === 'Solo')
              <p class="text-sm text-gray-600 mt-1">You're on the <strong>Solo plan</strong> — Option A (same-account sync) is included. Go to <a href="{{ route('connector.index') }}" class="text-navy-600 font-medium underline">Connector</a> to enable it.</p>
            @else
              <p class="text-sm text-gray-600 mt-1">You're on the <strong>{{ $entitlement->plan?->name ?? 'current' }} plan</strong>. Both connection options are available. <a href="{{ route('connector.index') }}" class="text-navy-600 font-medium underline">Go to Connector →</a></p>
            @endif
          @else
            <p class="text-sm text-gray-600 mt-1">Start a free trial to try the connector. <a href="{{ route('settings.billing') }}" class="text-navy-600 font-medium underline">View plans →</a></p>
          @endif
        </div>
      </div>
    </div>
  @else
    <div class="bg-navy-50 border border-navy-200 rounded-2xl p-6 mb-16 text-center">
      <p class="text-gray-700 mb-4">Sign in to check which connection option is included in your plan.</p>
      <div class="flex justify-center gap-4">
        <a href="{{ route('login') }}" class="px-5 py-2.5 bg-navy-600 text-white rounded-xl font-medium text-sm hover:bg-navy-700 transition">Sign In</a>
        <a href="{{ route('register') }}" class="px-5 py-2.5 border border-navy-200 text-navy-600 rounded-xl font-medium text-sm hover:bg-navy-50 transition">Start Free Trial</a>
      </div>
    </div>
  @endauth

  {{-- FAQ --}}
  <div>
    <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">Common questions</h2>
    <div class="space-y-4 max-w-3xl mx-auto" x-data="{ open: null }">
      @foreach([
        ['Do I need to know any technical stuff?','No. Option A is just a toggle in settings. Option B requires copying a code and sending it to your client — that\'s it. No technical knowledge needed.'],
        ['What if my client makes a mistake in the invoice?','The invoice appears in Lekhya\'s review queue before being posted to the ledger. You can reject it, ask them to fix it in Seedha Bill, and it will re-sync once corrected.'],
        ['Can I disconnect a client later?','Yes. Either you revoke the connection in Lekhya, or your client can disconnect from their Seedha Bill settings. Sync stops immediately.'],
        ['Does this work if my client is not on Seedha Bill?','Not yet. The Seedha Bill connector works specifically with Seedha Bill. We plan to add connectors for other billing apps in future.'],
        ['Is my client\'s data safe?','Yes. Your client\'s invoice data travels over encrypted connections and is stored only in your Lekhya account (tenant-isolated). Your client cannot see your books — they can only push invoices to you.'],
      ] as $i => [$q,$a])
      <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
        <button @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                class="w-full flex items-center justify-between px-6 py-4 text-left">
          <span class="font-medium text-gray-900 text-sm">{{ $q }}</span>
          <i class="fa fa-chevron-down text-gray-400 text-xs transition-transform duration-150" :class="open === {{ $i }} ? 'rotate-180' : ''"></i>
        </button>
        <div x-show="open === {{ $i }}" x-cloak class="px-6 pb-4 text-sm text-gray-600 leading-relaxed border-t border-gray-100">{{ $a }}</div>
      </div>
      @endforeach
    </div>
  </div>

</div>

{{-- CTA --}}
<section class="bg-green-700 text-white py-16">
  <div class="max-w-2xl mx-auto px-4 text-center">
    <h2 class="text-2xl font-bold mb-3">Ready to connect?</h2>
    <p class="text-green-200 mb-8">Set up in under 2 minutes. No credit card for the trial.</p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3 bg-white text-green-700 font-semibold rounded-xl hover:bg-green-50 transition">
        Start Free Trial <i class="fa fa-arrow-right ml-2"></i>
      </a>
      @auth
      <a href="{{ route('connector.index') }}" class="inline-flex items-center justify-center px-6 py-3 border border-white border-opacity-40 text-white font-medium rounded-xl hover:bg-white hover:bg-opacity-10 transition">
        Go to Connector <i class="fa fa-arrow-right ml-2"></i>
      </a>
      @endauth
    </div>
  </div>
</section>

@endsection
