@extends('layouts.marketing')
@section('title', 'Pricing — Lekhya')

@push('styles')
<style>
  /* prevent Alpine flash for non-default billing panels */
  [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div x-data="{
    billing: 'monthly',
    openFaq: null,
    faqs: [
        {
            q: 'Is there really a 14-day free trial?',
            a: 'Yes — every paid plan starts with a 14-day free trial with full access to all features of the chosen plan. No credit card required.'
        },
        {
            q: 'Will GST be charged on top of the listed price?',
            a: 'Yes. 18% GST (CGST+SGST or IGST as applicable) is added at checkout. A proper GST tax invoice is issued to your registered GSTIN.'
        },
        {
            q: 'Can I switch or upgrade my plan later?',
            a: 'Absolutely. Upgrade anytime — the change takes effect immediately and you are charged only the prorated difference. Downgrades apply at the start of the next billing cycle.'
        },
        {
            q: 'What is the difference between Lekhya ERP and Lekhya Pramaan?',
            a: 'Lekhya ERP is for businesses managing their own GST accounting. Lekhya Pramaan is the CA / Tax-Professional edition — it adds multi-client management, UDIN register, audit forms (Form 3CD / 3CB), DSC expiry tracking, compliance calendar, and white-label PDF reports.'
        },
        {
            q: 'Is the Lifetime plan really one-time — no future charges?',
            a: 'Yes — pay once, use forever. You receive all future updates within the same tier at no extra cost. Major new product lines may be offered as separate paid offerings.'
        },
        {
            q: 'Can I cancel my subscription at any time?',
            a: 'Yes. Cancel anytime from your account dashboard — no penalties, no questions asked. Your access continues until the end of the current billing period.'
        }
    ]
}" class="bg-white">

{{-- ═══════════════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════════════ --}}
<section class="bg-gradient-to-b from-navy-50 to-white pt-20 pb-10">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block bg-navy-600 text-white text-xs font-semibold px-4 py-1.5 rounded-full mb-5 uppercase tracking-widest">Pricing</span>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-navy-600 mb-5 leading-tight">Simple, Transparent Pricing</h1>
        <p class="text-lg text-gray-500">No hidden fees. Cancel anytime. 14-day free trial on all plans.</p>
        <p class="text-sm text-gray-400 mt-2">All prices in INR. +18% GST applicable at checkout.</p>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     BILLING TOGGLE  — sticky below nav
═══════════════════════════════════════════════════════════ --}}
<div class="sticky top-16 z-40 bg-white/95 backdrop-blur border-b border-gray-100 shadow-sm py-4">
    <div class="flex justify-center">
        <div class="inline-flex items-center bg-gray-100 rounded-xl p-1 gap-1">
            <button @click="billing='monthly'"
                :class="billing==='monthly' ? 'bg-white text-navy-600 shadow font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-5 py-2 rounded-lg text-sm transition-all">
                Monthly
            </button>
            <button @click="billing='yearly'"
                :class="billing==='yearly' ? 'bg-white text-navy-600 shadow font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="relative px-5 py-2 rounded-lg text-sm transition-all">
                Yearly
                <span class="absolute -top-3 -right-2 bg-green-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5 leading-tight">-20%</span>
            </button>
            <button @click="billing='lifetime'"
                :class="billing==='lifetime' ? 'bg-white text-navy-600 shadow font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-5 py-2 rounded-lg text-sm transition-all">
                Lifetime
            </button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 1 — LEKHYA ERP
═══════════════════════════════════════════════════════════ --}}
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-navy-600">Lekhya ERP</h2>
            <p class="text-gray-500 mt-2">GST-compliant double-entry accounting for businesses of every size.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-7 max-w-5xl mx-auto">

            {{-- ERP LITE --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-8 flex flex-col shadow-sm hover:shadow-md transition-shadow"
                 :class="billing === 'lifetime' ? 'opacity-40 pointer-events-none select-none' : ''">
                <div class="mb-2">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">ERP Lite</span>
                </div>

                <div class="mt-2 min-h-[5rem]">
                    <div x-show="billing === 'monthly'" x-cloak>
                        <span class="text-4xl font-extrabold text-navy-600">₹499</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                    <div x-show="billing === 'yearly'" x-cloak>
                        <span class="text-4xl font-extrabold text-navy-600">₹4,990</span>
                        <span class="text-gray-400 text-sm ml-1">/year</span>
                        <p class="text-green-600 text-xs mt-1 font-medium">Save ₹998 vs monthly</p>
                    </div>
                    <div x-show="billing === 'lifetime'" x-cloak>
                        <span class="text-xl font-semibold text-gray-400">Not available as lifetime</span>
                    </div>
                    {{-- show monthly price by default before Alpine boots --}}
                    <div x-show="false" style="display:block" class="js-hide">
                        <span class="text-4xl font-extrabold text-navy-600">₹499</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                </div>

                <p class="text-gray-500 text-sm mt-3 mb-6">Perfect for solo traders &amp; small businesses.</p>

                <a href="{{ route('register') }}"
                   class="block text-center bg-gray-100 hover:bg-gray-200 text-navy-600 font-semibold py-3 rounded-xl mb-7 transition">
                    Start Free Trial
                </a>

                <ul class="space-y-3 text-sm text-gray-600 mt-auto">
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>1 user seat</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>1 company</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Chart of accounts &amp; double-entry journals</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Sales &amp; purchase invoices</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Basic GST reports</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Bank reconciliation</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Tally XML import &amp; PDF export</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-bolt text-amber-500 mt-0.5 w-4 flex-shrink-0"></i><span><strong>150</strong> AI invoice scans / month</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-xmark text-gray-300 mt-0.5 w-4 flex-shrink-0"></i><span class="text-gray-400">Seedha Bill connector</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-xmark text-gray-300 mt-0.5 w-4 flex-shrink-0"></i><span class="text-gray-400">GST e-filing &amp; e-Invoice</span></li>
                </ul>
            </div>

            {{-- ERP PRO — highlighted --}}
            <div class="rounded-2xl border-2 border-navy-600 bg-white p-8 flex flex-col shadow-xl relative"
                 :class="billing === 'lifetime' ? 'opacity-40 pointer-events-none select-none' : ''">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 whitespace-nowrap">
                    <span class="bg-navy-600 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-lg">⭐ Most Popular</span>
                </div>

                <div class="mb-2 pt-2">
                    <span class="text-xs font-bold text-navy-600 uppercase tracking-widest">ERP Pro</span>
                </div>

                <div class="mt-2 min-h-[5rem]">
                    <div x-show="billing === 'monthly'" x-cloak>
                        <span class="text-4xl font-extrabold text-navy-600">₹1,299</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                    <div x-show="billing === 'yearly'" x-cloak>
                        <span class="text-4xl font-extrabold text-navy-600">₹12,990</span>
                        <span class="text-gray-400 text-sm ml-1">/year</span>
                        <p class="text-green-600 text-xs mt-1 font-medium">Save ₹2,598 vs monthly</p>
                    </div>
                    <div x-show="billing === 'lifetime'" x-cloak>
                        <span class="text-xl font-semibold text-gray-400">See ERP Lifetime →</span>
                    </div>
                    <div x-show="false" style="display:block" class="js-hide">
                        <span class="text-4xl font-extrabold text-navy-600">₹1,299</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                </div>

                <p class="text-gray-500 text-sm mt-3 mb-6">The complete GST ERP with AI &amp; connectors.</p>

                <a href="{{ route('register') }}"
                   class="block text-center bg-navy-600 hover:bg-navy-700 text-white font-semibold py-3 rounded-xl mb-7 transition">
                    Start Free Trial
                </a>

                <ul class="space-y-3 text-sm text-gray-600 mt-auto">
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>5 user seats</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>1 company</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Everything in ERP Lite</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-bolt text-amber-500 mt-0.5 w-4 flex-shrink-0"></i><span><strong>750</strong> AI invoice scans / month</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Seedha Bill live connector</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>GST e-filing (GSTR-1 &amp; 3B)</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>e-Invoice (IRN) &amp; e-Way Bill</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>API access &amp; priority support</span></li>
                </ul>
            </div>

            {{-- ERP LIFETIME --}}
            <div class="rounded-2xl border bg-white p-8 flex flex-col shadow-sm hover:shadow-md transition-shadow"
                 :class="billing === 'lifetime'
                    ? 'border-amber-400 ring-2 ring-amber-300 shadow-lg'
                    : 'border-gray-200'">
                <div class="mb-2">
                    <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">ERP Lifetime</span>
                </div>

                <div class="mt-2 min-h-[5rem]">
                    <span class="text-4xl font-extrabold text-navy-600">₹24,999</span>
                    <span class="text-gray-400 text-sm ml-1">one-time</span>
                    <p class="text-amber-600 text-xs mt-1 font-semibold">Pay once. Use forever.</p>
                </div>

                <p class="text-gray-500 text-sm mt-3 mb-6">Everything in ERP Pro — no subscriptions, ever.</p>

                <a href="{{ route('register') }}"
                   class="block text-center bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 rounded-xl mb-7 transition">
                    Buy Lifetime Access
                </a>

                <ul class="space-y-3 text-sm text-gray-600 mt-auto">
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>10 user seats</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>3 companies</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>All ERP Pro features</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-bolt text-amber-500 mt-0.5 w-4 flex-shrink-0"></i><span><strong>750</strong> AI invoice scans / month</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>All future ERP updates included</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Priority support, forever</span></li>
                </ul>
            </div>

        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 2 — LEKHYA PRAMAAN (CA Edition)
═══════════════════════════════════════════════════════════ --}}
<section class="py-16 bg-navy-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12">
            <span class="inline-block bg-navy-600 text-white text-xs font-semibold px-4 py-1.5 rounded-full mb-4 uppercase tracking-widest">CA Edition</span>
            <h2 class="text-3xl font-bold text-navy-600">Lekhya Pramaan</h2>
            <p class="text-gray-500 mt-2">For Chartered Accountants &amp; Tax Professionals — multi-client, audit-ready.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-7 max-w-5xl mx-auto">

            {{-- PRAMAAN LITE --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-8 flex flex-col shadow-sm hover:shadow-md transition-shadow"
                 :class="billing === 'lifetime' ? 'opacity-40 pointer-events-none select-none' : ''">
                <div class="mb-2">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Pramaan Lite</span>
                </div>

                <div class="mt-2 min-h-[5rem]">
                    <div x-show="billing === 'monthly'" x-cloak>
                        <span class="text-4xl font-extrabold text-navy-600">₹999</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                    <div x-show="billing === 'yearly'" x-cloak>
                        <span class="text-4xl font-extrabold text-navy-600">₹9,990</span>
                        <span class="text-gray-400 text-sm ml-1">/year</span>
                        <p class="text-green-600 text-xs mt-1 font-medium">Save ₹1,998 vs monthly</p>
                    </div>
                    <div x-show="billing === 'lifetime'" x-cloak>
                        <span class="text-xl font-semibold text-gray-400">Monthly &amp; Yearly only</span>
                    </div>
                    <div x-show="false" style="display:block" class="js-hide">
                        <span class="text-4xl font-extrabold text-navy-600">₹999</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                </div>

                <p class="text-gray-500 text-sm mt-3 mb-6">For CAs handling up to 10 clients.</p>

                <a href="{{ route('register') }}"
                   class="block text-center bg-gray-100 hover:bg-gray-200 text-navy-600 font-semibold py-3 rounded-xl mb-7 transition">
                    Start Free Trial
                </a>

                <ul class="space-y-3 text-sm text-gray-600 mt-auto">
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>10 client seats</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>All ERP Pro features</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>UDIN register &amp; tracking</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-bolt text-amber-500 mt-0.5 w-4 flex-shrink-0"></i><span><strong>750</strong> AI invoice scans / month</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Compliance calendar &amp; alerts</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-xmark text-gray-300 mt-0.5 w-4 flex-shrink-0"></i><span class="text-gray-400">Audit forms (3CD / 3CB)</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-xmark text-gray-300 mt-0.5 w-4 flex-shrink-0"></i><span class="text-gray-400">DSC expiry tracking</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-xmark text-gray-300 mt-0.5 w-4 flex-shrink-0"></i><span class="text-gray-400">White-label PDF reports</span></li>
                </ul>
            </div>

            {{-- PRAMAAN PRO — highlighted --}}
            <div class="rounded-2xl border-2 border-navy-600 bg-white p-8 flex flex-col shadow-xl relative"
                 :class="billing === 'lifetime' ? 'opacity-40 pointer-events-none select-none' : ''">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 whitespace-nowrap">
                    <span class="bg-navy-600 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-lg">⭐ Most Popular</span>
                </div>

                <div class="mb-2 pt-2">
                    <span class="text-xs font-bold text-navy-600 uppercase tracking-widest">Pramaan Pro</span>
                </div>

                <div class="mt-2 min-h-[5rem]">
                    <div x-show="billing === 'monthly'" x-cloak>
                        <span class="text-4xl font-extrabold text-navy-600">₹2,999</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                    <div x-show="billing === 'yearly'" x-cloak>
                        <span class="text-4xl font-extrabold text-navy-600">₹29,990</span>
                        <span class="text-gray-400 text-sm ml-1">/year</span>
                        <p class="text-green-600 text-xs mt-1 font-medium">Save ₹5,998 vs monthly</p>
                    </div>
                    <div x-show="billing === 'lifetime'" x-cloak>
                        <span class="text-xl font-semibold text-gray-400">See Pramaan Lifetime →</span>
                    </div>
                    <div x-show="false" style="display:block" class="js-hide">
                        <span class="text-4xl font-extrabold text-navy-600">₹2,999</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                </div>

                <p class="text-gray-500 text-sm mt-3 mb-6">Unlimited clients. Complete audit toolkit.</p>

                <a href="{{ route('register') }}"
                   class="block text-center bg-navy-600 hover:bg-navy-700 text-white font-semibold py-3 rounded-xl mb-7 transition">
                    Start Free Trial
                </a>

                <ul class="space-y-3 text-sm text-gray-600 mt-auto">
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Unlimited client seats</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Everything in Pramaan Lite</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-bolt text-amber-500 mt-0.5 w-4 flex-shrink-0"></i><span><strong>3,000</strong> AI invoice scans / month</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Audit forms (Form 3CD &amp; 3CB)</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>DSC expiry tracking</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>White-label PDF reports</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Priority support</span></li>
                </ul>
            </div>

            {{-- PRAMAAN LIFETIME --}}
            <div class="rounded-2xl border bg-white p-8 flex flex-col shadow-sm hover:shadow-md transition-shadow"
                 :class="billing === 'lifetime'
                    ? 'border-amber-400 ring-2 ring-amber-300 shadow-lg'
                    : 'border-gray-200'">
                <div class="mb-2">
                    <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">Pramaan Lifetime</span>
                </div>

                <div class="mt-2 min-h-[5rem]">
                    <span class="text-4xl font-extrabold text-navy-600">₹49,999</span>
                    <span class="text-gray-400 text-sm ml-1">one-time</span>
                    <p class="text-amber-600 text-xs mt-1 font-semibold">Pay once. Use forever.</p>
                </div>

                <p class="text-gray-500 text-sm mt-3 mb-6">All Pramaan Pro features — no recurring billing.</p>

                <a href="{{ route('register') }}"
                   class="block text-center bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 rounded-xl mb-7 transition">
                    Buy Lifetime Access
                </a>

                <ul class="space-y-3 text-sm text-gray-600 mt-auto">
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Unlimited client seats</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>All Pramaan Pro features</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>All future Pramaan updates</span></li>
                    <li class="flex items-start gap-2"><i class="fa fa-check text-green-500 mt-0.5 w-4 flex-shrink-0"></i><span>Priority support, forever</span></li>
                </ul>
            </div>

        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 3 — COMBO PACKS
═══════════════════════════════════════════════════════════ --}}
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-navy-600">Combo Packs</h2>
            <p class="text-gray-500 mt-2">Bundle Lekhya with SeedhaBill integration and save more.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-7 max-w-5xl mx-auto">

            {{-- COMBO: SeedhaBill + ERP --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-8 flex flex-col shadow-sm hover:shadow-md transition-shadow">
                <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center mb-5">
                    <i class="fa fa-link text-blue-600 text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-navy-600 mb-1">SeedhaBill + ERP Pro</h3>
                <p class="text-gray-500 text-sm mb-5">Everything in ERP Pro, bundled with SeedhaBill live sync.</p>

                <div class="mb-5">
                    <div x-show="billing === 'monthly'" x-cloak>
                        <span class="text-3xl font-extrabold text-navy-600">₹1,499</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                    <div x-show="billing === 'yearly'" x-cloak>
                        <span class="text-3xl font-extrabold text-navy-600">₹14,990</span>
                        <span class="text-gray-400 text-sm ml-1">/year</span>
                        <p class="text-green-600 text-xs mt-1 font-medium">Save ₹2,998 vs monthly</p>
                    </div>
                    <div x-show="billing === 'lifetime'" x-cloak>
                        <span class="text-3xl font-extrabold text-navy-600">₹29,999</span>
                        <span class="text-gray-400 text-sm ml-1">one-time</span>
                    </div>
                    <div x-show="false" style="display:block" class="js-hide">
                        <span class="text-3xl font-extrabold text-navy-600">₹1,499</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                </div>

                <a href="{{ route('register') }}"
                   class="block text-center bg-gray-100 hover:bg-gray-200 text-navy-600 font-semibold py-2.5 rounded-xl mb-6 text-sm transition">
                    Get This Bundle
                </a>

                <ul class="space-y-2.5 text-sm text-gray-600 mt-auto">
                    <li class="flex items-center gap-2"><i class="fa fa-check text-green-500 w-4 flex-shrink-0"></i>All ERP Pro features</li>
                    <li class="flex items-center gap-2"><i class="fa fa-check text-green-500 w-4 flex-shrink-0"></i>SeedhaBill real-time sync</li>
                    <li class="flex items-center gap-2"><i class="fa fa-check text-green-500 w-4 flex-shrink-0"></i>Automatic invoice import &amp; matching</li>
                    <li class="flex items-center gap-2"><i class="fa fa-check text-green-500 w-4 flex-shrink-0"></i>5 user seats · 1 company</li>
                    <li class="flex items-center gap-2"><i class="fa fa-bolt text-amber-500 w-4 flex-shrink-0"></i><strong class="mr-1">1,000</strong> AI invoice scans / month</li>
                </ul>
            </div>

            {{-- COMBO: SeedhaBill + Pramaan — highlighted --}}
            <div class="rounded-2xl border-2 border-navy-600 bg-white p-8 flex flex-col shadow-xl relative">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 whitespace-nowrap">
                    <span class="bg-navy-600 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-lg">Best for CAs</span>
                </div>
                <div class="w-11 h-11 bg-navy-50 rounded-xl flex items-center justify-center mb-5 mt-2">
                    <i class="fa fa-building-columns text-navy-600 text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-navy-600 mb-1">SeedhaBill + Pramaan Pro</h3>
                <p class="text-gray-500 text-sm mb-5">The full CA toolkit — Pramaan Pro with SeedhaBill for all clients.</p>

                <div class="mb-5">
                    <div x-show="billing === 'monthly'" x-cloak>
                        <span class="text-3xl font-extrabold text-navy-600">₹3,499</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                    <div x-show="billing === 'yearly'" x-cloak>
                        <span class="text-3xl font-extrabold text-navy-600">₹34,990</span>
                        <span class="text-gray-400 text-sm ml-1">/year</span>
                        <p class="text-green-600 text-xs mt-1 font-medium">Save ₹6,998 vs monthly</p>
                    </div>
                    <div x-show="billing === 'lifetime'" x-cloak>
                        <span class="text-3xl font-extrabold text-navy-600">₹69,999</span>
                        <span class="text-gray-400 text-sm ml-1">one-time</span>
                    </div>
                    <div x-show="false" style="display:block" class="js-hide">
                        <span class="text-3xl font-extrabold text-navy-600">₹3,499</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                </div>

                <a href="{{ route('register') }}"
                   class="block text-center bg-navy-600 hover:bg-navy-700 text-white font-semibold py-2.5 rounded-xl mb-6 text-sm transition">
                    Get This Bundle
                </a>

                <ul class="space-y-2.5 text-sm text-gray-600 mt-auto">
                    <li class="flex items-center gap-2"><i class="fa fa-check text-green-500 w-4 flex-shrink-0"></i>All Pramaan Pro features</li>
                    <li class="flex items-center gap-2"><i class="fa fa-check text-green-500 w-4 flex-shrink-0"></i>SeedhaBill for all clients</li>
                    <li class="flex items-center gap-2"><i class="fa fa-check text-green-500 w-4 flex-shrink-0"></i>Multi-client SeedhaBill dashboard</li>
                    <li class="flex items-center gap-2"><i class="fa fa-check text-green-500 w-4 flex-shrink-0"></i>Unlimited clients · 20 users</li>
                    <li class="flex items-center gap-2"><i class="fa fa-bolt text-amber-500 w-4 flex-shrink-0"></i><strong class="mr-1">3,500</strong> AI invoice scans / month</li>
                </ul>
            </div>

            {{-- COMBO: Full Suite --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-8 flex flex-col shadow-sm hover:shadow-md transition-shadow">
                <div class="w-11 h-11 bg-amber-50 rounded-xl flex items-center justify-center mb-5">
                    <i class="fa fa-star text-amber-500 text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-navy-600 mb-1">Full Suite</h3>
                <p class="text-gray-500 text-sm mb-5">All current &amp; future Lekhya apps — the complete platform, one bill.</p>

                <div class="mb-5">
                    <div x-show="billing === 'monthly'" x-cloak>
                        <span class="text-3xl font-extrabold text-navy-600">₹4,999</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                    <div x-show="billing === 'yearly'" x-cloak>
                        <span class="text-3xl font-extrabold text-navy-600">₹49,990</span>
                        <span class="text-gray-400 text-sm ml-1">/year</span>
                        <p class="text-green-600 text-xs mt-1 font-medium">Save ₹9,998 vs monthly</p>
                    </div>
                    <div x-show="billing === 'lifetime'" x-cloak>
                        <span class="text-3xl font-extrabold text-navy-600">₹99,999</span>
                        <span class="text-gray-400 text-sm ml-1">one-time</span>
                    </div>
                    <div x-show="false" style="display:block" class="js-hide">
                        <span class="text-3xl font-extrabold text-navy-600">₹4,999</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                </div>

                <a href="{{ route('register') }}"
                   class="block text-center bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 rounded-xl mb-6 text-sm transition">
                    Get Full Suite
                </a>

                <ul class="space-y-2.5 text-sm text-gray-600 mt-auto">
                    <li class="flex items-center gap-2"><i class="fa fa-check text-green-500 w-4 flex-shrink-0"></i>All ERP + Pramaan features</li>
                    <li class="flex items-center gap-2"><i class="fa fa-check text-green-500 w-4 flex-shrink-0"></i>SeedhaBill full integration</li>
                    <li class="flex items-center gap-2"><i class="fa fa-check text-green-500 w-4 flex-shrink-0"></i>All future apps included free</li>
                    <li class="flex items-center gap-2"><i class="fa fa-check text-green-500 w-4 flex-shrink-0"></i>50 users · dedicated support</li>
                </ul>
            </div>

        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 4 — FEATURE COMPARISON TABLE (ERP tiers)
═══════════════════════════════════════════════════════════ --}}
<section class="py-16 bg-navy-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-navy-600">Full Feature Comparison</h2>
            <p class="text-gray-500 mt-2">Lekhya ERP — Lite vs Pro vs Lifetime</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[560px]">
                    <thead>
                        <tr class="bg-navy-600 text-white">
                            <th class="text-left px-6 py-4 font-semibold w-1/2">Feature</th>
                            <th class="text-center px-4 py-4 font-semibold">Lite</th>
                            <th class="text-center px-4 py-4 font-semibold bg-navy-700">Pro</th>
                            <th class="text-center px-4 py-4 font-semibold">Lifetime</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">

                        {{-- Accounting --}}
                        <tr class="bg-gray-50">
                            <td colspan="4" class="px-6 py-2.5 text-xs font-bold text-gray-500 uppercase tracking-widest">Accounting</td>
                        </tr>
                        @foreach ([
                            ['Chart of Accounts (standard Indian CoA)', true, true, true],
                            ['Double-entry journal engine (immutable)', true, true, true],
                            ['Sales & purchase invoices (with line items)', true, true, true],
                            ['Bank reconciliation', true, true, true],
                            ['PDF export (P&L, Balance Sheet, Trial Balance)', true, true, true],
                            ['Tally XML migration', true, true, true],
                        ] as [$label, $lite, $pro, $life])
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-700">{!! $label !!}</td>
                            <td class="text-center px-4 py-3">
                                @if($lite === true) <i class="fa fa-check text-green-500"></i>
                                @elseif($lite === false) <i class="fa fa-xmark text-gray-300"></i>
                                @else <span class="text-gray-500 text-xs">{{ $lite }}</span>
                                @endif
                            </td>
                            <td class="text-center px-4 py-3 bg-navy-50/30">
                                @if($pro === true) <i class="fa fa-check text-green-500"></i>
                                @elseif($pro === false) <i class="fa fa-xmark text-gray-300"></i>
                                @else <span class="text-gray-500 text-xs">{{ $pro }}</span>
                                @endif
                            </td>
                            <td class="text-center px-4 py-3">
                                @if($life === true) <i class="fa fa-check text-green-500"></i>
                                @elseif($life === false) <i class="fa fa-xmark text-gray-300"></i>
                                @else <span class="text-gray-500 text-xs">{{ $life }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach

                        {{-- GST --}}
                        <tr class="bg-gray-50">
                            <td colspan="4" class="px-6 py-2.5 text-xs font-bold text-gray-500 uppercase tracking-widest">GST</td>
                        </tr>
                        @foreach ([
                            ['GST rate engine (CGST / SGST / IGST)', true, true, true],
                            ['GSTIN validation & HSN/SAC lookup', true, true, true],
                            ['GST reports — GSTR-1 & 3B', 'Basic', true, true],
                            ['GSTR-2B reconciliation', false, true, true],
                            ['GST e-filing (GSTR-1 & 3B via GSP)', false, true, true],
                            ['e-Invoice (IRN generation)', false, true, true],
                            ['e-Way Bill', false, true, true],
                        ] as [$label, $lite, $pro, $life])
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-700">{!! $label !!}</td>
                            <td class="text-center px-4 py-3">
                                @if($lite === true) <i class="fa fa-check text-green-500"></i>
                                @elseif($lite === false) <i class="fa fa-xmark text-gray-300"></i>
                                @else <span class="text-gray-500 text-xs">{{ $lite }}</span>
                                @endif
                            </td>
                            <td class="text-center px-4 py-3 bg-navy-50/30">
                                @if($pro === true) <i class="fa fa-check text-green-500"></i>
                                @elseif($pro === false) <i class="fa fa-xmark text-gray-300"></i>
                                @else <span class="text-gray-500 text-xs">{{ $pro }}</span>
                                @endif
                            </td>
                            <td class="text-center px-4 py-3">
                                @if($life === true) <i class="fa fa-check text-green-500"></i>
                                @elseif($life === false) <i class="fa fa-xmark text-gray-300"></i>
                                @else <span class="text-gray-500 text-xs">{{ $life }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach

                        {{-- AI & Automation --}}
                        <tr class="bg-gray-50">
                            <td colspan="4" class="px-6 py-2.5 text-xs font-bold text-gray-500 uppercase tracking-widest">AI &amp; Automation</td>
                        </tr>
                        @foreach ([
                            ['AI OCR invoice extraction', false, true, true],
                            ['AI auto-coding (ledger suggestion)', false, true, true],
                            ['AI reconciliation matching', false, true, true],
                            ['Seedha Bill connector (live sync)', false, true, true],
                            ['Duplicate invoice detection', false, true, true],
                        ] as [$label, $lite, $pro, $life])
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-700">{!! $label !!}</td>
                            <td class="text-center px-4 py-3">
                                @if($lite === true) <i class="fa fa-check text-green-500"></i>
                                @elseif($lite === false) <i class="fa fa-xmark text-gray-300"></i>
                                @else <span class="text-gray-500 text-xs">{{ $lite }}</span>
                                @endif
                            </td>
                            <td class="text-center px-4 py-3 bg-navy-50/30">
                                @if($pro === true) <i class="fa fa-check text-green-500"></i>
                                @elseif($pro === false) <i class="fa fa-xmark text-gray-300"></i>
                                @else <span class="text-gray-500 text-xs">{{ $pro }}</span>
                                @endif
                            </td>
                            <td class="text-center px-4 py-3">
                                @if($life === true) <i class="fa fa-check text-green-500"></i>
                                @elseif($life === false) <i class="fa fa-xmark text-gray-300"></i>
                                @else <span class="text-gray-500 text-xs">{{ $life }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach

                        {{-- Limits & Support --}}
                        <tr class="bg-gray-50">
                            <td colspan="4" class="px-6 py-2.5 text-xs font-bold text-gray-500 uppercase tracking-widest">Limits &amp; Support</td>
                        </tr>
                        @foreach ([
                            ['User seats', '1', '5', '10'],
                            ['Companies', '1', '1', '3'],
                            ['API access', false, true, true],
                            ['Priority support', false, true, true],
                            ['All future ERP updates', false, false, true],
                        ] as [$label, $lite, $pro, $life])
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-700">{!! $label !!}</td>
                            <td class="text-center px-4 py-3">
                                @if($lite === true) <i class="fa fa-check text-green-500"></i>
                                @elseif($lite === false) <i class="fa fa-xmark text-gray-300"></i>
                                @else <span class="font-semibold text-gray-700">{{ $lite }}</span>
                                @endif
                            </td>
                            <td class="text-center px-4 py-3 bg-navy-50/30">
                                @if($pro === true) <i class="fa fa-check text-green-500"></i>
                                @elseif($pro === false) <i class="fa fa-xmark text-gray-300"></i>
                                @else <span class="font-semibold text-gray-700">{{ $pro }}</span>
                                @endif
                            </td>
                            <td class="text-center px-4 py-3">
                                @if($life === true) <i class="fa fa-check text-green-500"></i>
                                @elseif($life === false) <i class="fa fa-xmark text-gray-300"></i>
                                @else <span class="font-semibold text-gray-700">{{ $life }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 4b — AI credits & top-ups
═══════════════════════════════════════════════════════════ --}}
<section class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <span class="text-xs font-bold text-amber-600 uppercase tracking-widest"><i class="fa fa-bolt mr-1"></i>AI credits</span>
            <h2 class="text-3xl font-bold text-navy-600 mt-1">Powered by Lekhya AI</h2>
            <p class="text-gray-500 mt-2">Every plan includes a monthly pool of AI credits. Need more? Top up any time.</p>
        </div>
        <div class="grid sm:grid-cols-3 gap-5">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wider">1 credit</p>
                <p class="text-2xl font-extrabold text-navy-600 mt-1">1 AI action</p>
                <p class="text-sm text-gray-500 mt-2">An invoice scan, an AI question, or one auto-coding — each uses a single credit.</p>
            </div>
            <div class="bg-navy-600 rounded-2xl shadow-sm p-6 text-center text-white ring-2 ring-amber-400">
                <p class="text-xs text-navy-200 uppercase tracking-wider">Top-up pack</p>
                <p class="text-3xl font-extrabold mt-1">₹99<span class="text-base font-medium text-navy-200"> / 100</span></p>
                <p class="text-sm text-navy-100 mt-2">Extra AI credits at just <strong>₹0.99 each</strong> — added instantly, never expire.</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Included monthly</p>
                <p class="text-2xl font-extrabold text-navy-600 mt-1">150–3,000</p>
                <p class="text-sm text-gray-500 mt-2">Free credits every month, scaling by plan — resets on the 1st. No card needed to start.</p>
            </div>
        </div>
        <p class="text-center text-xs text-gray-400 mt-5">Credits cover all AI features — invoice OCR, auto-classification, AI Q&amp;A and account coding — on Lekhya's managed AI. No separate AI subscription or API key required.</p>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 5 — FAQ  (Alpine.js accordion)
═══════════════════════════════════════════════════════════ --}}
<section class="py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-navy-600">Frequently Asked Questions</h2>
            <p class="text-gray-500 mt-2">
                Still have questions?
                <a href="{{ route('marketing.contact') }}" class="text-navy-600 hover:underline">Talk to us →</a>
            </p>
        </div>

        <div class="space-y-3">
            <template x-for="(item, i) in faqs" :key="i">
                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                    <button
                        @click="openFaq = (openFaq === i) ? null : i"
                        class="w-full flex justify-between items-center px-6 py-4 text-left hover:bg-gray-50 transition-colors">
                        <span class="font-semibold text-gray-800 text-sm sm:text-base pr-4" x-text="item.q"></span>
                        <i class="fa flex-shrink-0 text-navy-600 transition-transform duration-200"
                           :class="openFaq === i ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                    <div
                        x-show="openFaq === i"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-gray-600 text-sm leading-relaxed">
                        <p x-text="item.a"></p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 6 — CTA BANNER
═══════════════════════════════════════════════════════════ --}}
<section class="py-20 bg-navy-600">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4 leading-tight">
            Start your 14-day free trial
        </h2>
        <p class="text-blue-200 text-lg mb-10">No credit card required. Full access to all features. Cancel anytime.</p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}"
               class="inline-flex items-center justify-center gap-2 bg-white text-navy-600 font-bold px-8 py-4 rounded-xl text-lg hover:bg-gray-100 transition shadow-lg">
                Get Started Free
                <i class="fa fa-arrow-right"></i>
            </a>
            <a href="{{ route('marketing.features') }}"
               class="inline-flex items-center justify-center gap-2 border border-blue-400 text-white font-semibold px-8 py-4 rounded-xl text-lg hover:bg-navy-700 transition">
                See All Features
            </a>
        </div>

        <p class="mt-8 text-blue-300 text-sm flex flex-wrap items-center justify-center gap-4">
            <span><i class="fa fa-shield-halved mr-1.5"></i>GST-compliant &amp; secure</span>
            <span><i class="fa fa-rotate-left mr-1.5"></i>Cancel anytime</span>
            <span><i class="fa fa-headset mr-1.5"></i>Support in Hindi &amp; English</span>
        </p>
    </div>
</section>

</div>{{-- end x-data --}}

@push('scripts')
<script>
    // After Alpine boots, remove the SSR-fallback price divs
    document.addEventListener('alpine:init', () => {
        document.querySelectorAll('.js-hide').forEach(el => el.remove());
    });
</script>
@endpush

@endsection
