@extends('layouts.marketing')
@section('title', 'Lekhya — AI GST Accounting ERP India')

@section('content')
{{-- Hero --}}
<section class="bg-gradient-to-br from-navy-600 to-navy-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <div class="inline-flex items-center px-3 py-1 bg-blue-500 bg-opacity-20 border border-blue-400 border-opacity-30 rounded-full text-blue-200 text-sm mb-6">
                    <i class="fa fa-sparkles mr-2"></i> AI-powered · GST-ready · India-first
                </div>
                <h1 class="text-4xl sm:text-5xl font-bold leading-tight mb-6">
                    Accounting jab<br>
                    <span class="text-blue-300">seedha-saadha</span> ho,<br>
                    toh kaam asaan hai
                </h1>
                <p class="text-lg text-gray-300 mb-8">
                    Complete GST-compliant accounting ERP for India. Double-entry ledger, AI invoice extraction,
                    e-invoice, GSTR filing, and a live link to <strong class="text-white">Seedha Bill</strong> — all in one login.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3 bg-blue-500 hover:bg-blue-400 text-white font-semibold rounded-xl transition text-lg">
                        Start Free 14-Day Trial <i class="fa fa-arrow-right ml-2"></i>
                    </a>
                    <a href="{{ route('marketing.flows') }}" class="inline-flex items-center justify-center px-6 py-3 border border-white border-opacity-30 text-white hover:bg-white hover:bg-opacity-10 font-medium rounded-xl transition">
                        <i class="fa fa-play-circle mr-2"></i> See how it works
                    </a>
                </div>
                <p class="mt-4 text-sm text-gray-400"><i class="fa fa-check mr-1 text-green-400"></i> No credit card · <i class="fa fa-check mr-1 text-green-400"></i> GST-ready from day 1 · <i class="fa fa-check mr-1 text-green-400"></i> Tally import</p>
            </div>

            <div class="hidden lg:block">
                <div class="bg-white bg-opacity-5 border border-white border-opacity-10 rounded-2xl p-6 space-y-3">
                    <div class="flex items-center justify-between p-3 bg-green-500 bg-opacity-10 rounded-lg border border-green-500 border-opacity-20">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center"><i class="fa fa-check text-white text-sm"></i></div>
                            <div>
                                <p class="text-white text-sm font-medium">Invoice SB-2024-089 received</p>
                                <p class="text-gray-400 text-xs">Seedha Bill → Lekhya | ₹18,000 + GST</p>
                            </div>
                        </div>
                        <span class="text-green-400 text-xs font-medium">Auto-posted</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-blue-500 bg-opacity-10 rounded-lg border border-blue-500 border-opacity-20">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center"><i class="fa fa-robot text-white text-sm"></i></div>
                            <div>
                                <p class="text-white text-sm font-medium">AI: IRN generated</p>
                                <p class="text-gray-400 text-xs">e-Invoice · ACK: 1320241234567</p>
                            </div>
                        </div>
                        <span class="text-blue-400 text-xs font-medium">GST ✓</span>
                    </div>
                    <div class="p-3 bg-white bg-opacity-5 rounded-lg">
                        <p class="text-gray-400 text-xs mb-2">This Month — Net P&L</p>
                        <p class="text-white text-2xl font-bold">₹2,34,500 <span class="text-green-400 text-base">↑ 12%</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Features Grid --}}
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Everything an accountant needs</h2>
            <p class="text-lg text-gray-600">No jargon. No clutter. Just what you need, when you need it.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                ['fa-book-open', 'navy', 'Double-Entry Ledger', 'Bank-grade accuracy. Every entry balanced. Posted journals immutable — corrections via reversing entries only.'],
                ['fa-file-invoice-dollar', 'green', 'GST-Ready Invoicing', 'HSN/SAC auto-rate engine, e-invoice IRN generation, e-way bill, GSTR-1/3B in one click.'],
                ['fa-plug', 'purple', 'Seedha Bill Connector', 'Freelancers push invoices from Seedha Bill; they auto-land in your Lekhya — verified, posted, locked.'],
                ['fa-robot', 'blue', 'AI Invoice Extraction', 'Upload a PDF or image; AI extracts line items, GSTIN, HSN codes. You approve — AI never posts directly.'],
                ['fa-arrows-rotate', 'orange', 'GSTR-2B Reconciliation', 'Match your purchase invoices against the 2B statement. Mismatches flagged automatically.'],
                ['fa-building-columns', 'teal', 'Bank Reconciliation', 'Upload passbook CSV/PDF; Lekhya auto-matches against journal entries. One-click mark cleared.'],
                ['fa-file-import', 'yellow', 'Tally ERP Migration', 'Import all masters and vouchers from Tally ERP 9 / Tally Prime. Zero data loss, one wizard.'],
                ['fa-certificate', 'red', 'Lekhya Pramaan (CA)', 'UDIN, DSC, 3CD/3CB forms, compliance calendar, multi-client dashboard — built for Chartered Accountants.'],
                ['fa-chart-bar', 'indigo', 'Live Reports', 'P&L, Balance Sheet, Trial Balance, AR/AP aging — real-time, PDF export, Schedule III format.'],
            ] as [$icon, $color, $title, $desc])
            <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition">
                <div class="w-10 h-10 rounded-lg bg-{{ $color }}-100 flex items-center justify-center mb-4">
                    <i class="fa {{ $icon }} text-{{ $color }}-600"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">{{ $title }}</h3>
                <p class="text-sm text-gray-600">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Pricing teaser --}}
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Pay by need. Nothing extra.</h2>
        <p class="text-lg text-gray-600 mb-12">Solo business · Practice accountant · CA firm · We have a plan for each.</p>
        <div class="grid sm:grid-cols-3 gap-6 max-w-4xl mx-auto">
            @foreach([
                ['Solo', 'own books only', '₹499', '/month', false, 'Your own business accounts. Seedha Bill Mode A auto-sync included.'],
                ['Practice', 'up to 10 clients', '₹1,299', '/month', true, 'Connect up to 10 freelancers via Seedha Bill tokens.'],
                ['Firm', 'up to 30 clients', '₹2,999', '/month', false, '30 client seats + staff user seats for your team.'],
            ] as [$name, $sub, $price, $cycle, $popular, $desc])
            <div class="rounded-2xl p-6 border-2 {{ $popular ? 'border-navy-600 shadow-xl' : 'border-gray-200' }} relative">
                @if($popular) <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-navy-600 text-white text-xs font-bold px-3 py-1 rounded-full">Most Popular</span> @endif
                <h3 class="font-bold text-xl text-gray-900">Lekhya {{ $name }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ $sub }}</p>
                <p class="text-3xl font-bold text-navy-600">{{ $price }}<span class="text-base font-normal text-gray-500">{{ $cycle }}</span></p>
                <p class="mt-3 text-sm text-gray-600">{{ $desc }}</p>
                <a href="{{ route('register') }}" class="mt-6 block w-full text-center py-2.5 rounded-lg {{ $popular ? 'bg-navy-600 text-white' : 'border border-navy-600 text-navy-600' }} font-medium hover:opacity-90 transition">
                    Start Free Trial
                </a>
            </div>
            @endforeach
        </div>
        <p class="mt-8 text-sm text-gray-500">
            Also available: <strong>Lekhya Pramaan</strong> for Chartered Accountants (UDIN, DSC, Audit toolkits).
            <a href="{{ route('marketing.pricing') }}" class="text-navy-600 font-medium">See full pricing →</a>
        </p>
    </div>
</section>

{{-- CTA --}}
<section class="bg-navy-600 py-16">
    <div class="max-w-3xl mx-auto text-center px-4">
        <h2 class="text-3xl font-bold text-white mb-4">Ready to move beyond Excel?</h2>
        <p class="text-lg text-blue-200 mb-8">Start your 14-day free trial. Import from Tally in minutes. No credit card needed.</p>
        <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 bg-white text-navy-600 font-bold rounded-xl text-lg hover:bg-gray-50 transition">
            Get started free <i class="fa fa-arrow-right ml-2"></i>
        </a>
    </div>
</section>
@endsection
