<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Lekhya') — Lekhya AI ERP</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzMiAzMiI+CiAgPHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiByeD0iNyIgZmlsbD0iIzFCMkE0QSIvPgogIDxnIHRyYW5zZm9ybT0idHJhbnNsYXRlKDcgOCkgc2NhbGUoMC43NSkiPgogICAgPHBhdGggZD0iTTMgM3YxNmEyIDIgMCAwIDAgMiAyaDE2IiBmaWxsPSJub25lIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjIuNiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+CiAgICA8cGF0aCBkPSJtMTkgOS01IDUtNC00LTMgMyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIyLjYiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgogIDwvZz4KPC9zdmc+Cg==">
    <link rel="alternate icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { navy: { 50:'#f0f3f8', 100:'#d9e1ef', 200:'#b3c4df', 300:'#7fa0c9', 400:'#4f7ab0', 500:'#2e5a94', 600:'#1B2A4A', 700:'#162240', 800:'#111a33', 900:'#0c1226' } } } } }</script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @stack('styles')
</head>
<body class="h-full bg-gray-50 font-sans" x-data="{ sidebarOpen: false }">

{{-- Sidebar --}}
<div class="flex h-full">
    {{-- Mobile sidebar overlay --}}
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden" @click="sidebarOpen=false"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col bg-navy-600 lg:static lg:z-auto"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           style="transition: transform 0.2s">

        {{-- Logo --}}
        <div class="flex items-center h-16 px-4 border-b border-navy-500">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-blue-400 rounded-lg flex items-center justify-center">
                    <img src="{{ asset('logo-mark.svg') }}" alt="Lekhya" class="w-5 h-5">
                </div>
                <div>
                    <span class="text-white font-bold text-lg">Lekhya</span>
                    @if(auth()->user()->tenant?->isPramaan())
                        <span class="ml-1 text-xs text-blue-300">Pramaan</span>
                    @endif
                </div>
            </a>
        </div>

        {{-- Trial badge — precise day/hour countdown --}}
        @php
            $entitlement = auth()->user()->tenant?->entitlements()->where('app','lekhya')->where('is_active',true)->first();
            $trialEnd = $entitlement?->trial_ends_at;
            $tDays  = $trialEnd ? (int) now()->startOfDay()->diffInDays($trialEnd->copy()->startOfDay(), false) : 0;
            $tHours = $trialEnd ? (int) now()->diffInHours($trialEnd, false) : 0;
        @endphp
        @if($trialEnd && $trialEnd->isFuture())
        <div class="mx-3 mt-3 px-3 py-2 bg-amber-500 bg-opacity-20 rounded-lg border border-amber-400">
            <p class="text-amber-200 text-xs font-medium">
                Trial: {{ $tDays >= 1 ? $tDays.' day'.($tDays == 1 ? '' : 's') : max(1, $tHours).' hour'.($tHours == 1 ? '' : 's') }} left
            </p>
            <p class="text-amber-200 text-[10px] opacity-70 mt-0.5">ends {{ $trialEnd->format('d M Y') }}</p>
        </div>
        @endif

        {{-- Navigation --}}
        <nav id="sidebar-nav" class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            <a href="{{ route('dashboard') }}" class="nav-link @active('dashboard')">
                <i class="fa fa-gauge-high w-5"></i> <span>Dashboard</span>
            </a>

            <div class="pt-3 pb-1">
                <p class="px-2 text-xs font-semibold text-navy-300 uppercase tracking-wider">Accounting</p>
            </div>
            <a href="{{ route('accounting.invoices.index') }}" class="nav-link @active('accounting.invoices*')">
                <i class="fa fa-file-invoice w-5"></i> <span>Sales Invoices</span>
            </a>
            <a href="{{ route('accounting.invoices.index') }}?type=purchase" class="nav-link">
                <i class="fa fa-cart-shopping w-5"></i> <span>Purchase Invoices</span>
            </a>
            <a href="{{ route('accounting.parties.index') }}" class="nav-link @active('accounting.parties*')">
                <i class="fa fa-address-book w-5"></i> <span>Vendors &amp; Customers</span>
            </a>
            <a href="{{ route('accounting.payments.pending') }}" class="nav-link @active('accounting.payments.pending', 'accounting.payments.bankfile*', 'accounting.payments.export')">
                <i class="fa fa-money-bill-wave w-5"></i> <span>Pending Payments</span>
            </a>
            <a href="{{ route('accounting.payments.history') }}" class="nav-link @active('accounting.payments.record*', 'accounting.payments.history', 'accounting.payments.show')">
                <i class="fa fa-hand-holding-dollar w-5"></i> <span>Receipts &amp; Payments</span>
            </a>
            <a href="{{ route('accounting.journals.index') }}" class="nav-link @active('accounting.journals*')">
                <i class="fa fa-book w-5"></i> <span>Journal Vouchers</span>
            </a>
            <a href="{{ route('accounting.accounts.index') }}" class="nav-link @active('accounting.accounts*')">
                <i class="fa fa-sitemap w-5"></i> <span>Chart of Accounts</span>
            </a>
            <a href="{{ route('accounting.products.index') }}" class="nav-link @active('accounting.products*')">
                <i class="fa fa-boxes-stacked w-5"></i> <span>Inventory / Products</span>
            </a>

            <div class="pt-3 pb-1">
                <p class="px-2 text-xs font-semibold text-navy-300 uppercase tracking-wider">Reports</p>
            </div>
            <a href="{{ route('accounting.reports.index') }}" class="nav-link @active('accounting.reports.index')">
                <i class="fa fa-folder-open w-5"></i> <span>All Reports</span>
            </a>
            <a href="{{ route('accounting.reports.pl') }}" class="nav-link">
                <i class="fa fa-chart-line w-5"></i> <span>Profit & Loss</span>
            </a>
            <a href="{{ route('accounting.reports.bs') }}" class="nav-link">
                <i class="fa fa-balance-scale w-5"></i> <span>Balance Sheet</span>
            </a>
            <a href="{{ route('accounting.reports.tb') }}" class="nav-link">
                <i class="fa fa-table w-5"></i> <span>Trial Balance</span>
            </a>
            <a href="{{ route('accounting.reports.ar') }}" class="nav-link">
                <i class="fa fa-clock w-5"></i> <span>AR / AP Aging</span>
            </a>

            <div class="pt-3 pb-1">
                <p class="px-2 text-xs font-semibold text-navy-300 uppercase tracking-wider">GST</p>
            </div>
            <a href="{{ route('gst.dashboard') }}" class="nav-link @active('gst.dashboard') @active('gst.validate') @active('gst.einvoice*')">
                <i class="fa fa-landmark w-5"></i> <span>GST Dashboard</span>
            </a>
            <a href="{{ route('gst.gstr1') }}" class="nav-link @active('gst.gstr1*')">
                <i class="fa fa-file-text w-5"></i> <span>GSTR-1</span>
            </a>
            <a href="{{ route('gst.gstr3b') }}" class="nav-link @active('gst.gstr3b')">
                <i class="fa fa-file-text w-5"></i> <span>GSTR-3B</span>
            </a>
            <a href="{{ route('gst.gstr2b') }}" class="nav-link @active('gst.gstr2b*')">
                <i class="fa fa-arrows-rotate w-5"></i> <span>GSTR-2B Recon</span>
            </a>

            <div class="pt-3 pb-1">
                <p class="px-2 text-xs font-semibold text-navy-300 uppercase tracking-wider">Banking</p>
            </div>
            <a href="{{ route('banking.index') }}" class="nav-link @active('banking.*')">
                <i class="fa fa-building-columns w-5"></i> <span>Reconciliation</span>
            </a>

            <div class="pt-3 pb-1">
                <p class="px-2 text-xs font-semibold text-navy-300 uppercase tracking-wider">Connector</p>
            </div>
            <a href="{{ route('connector.index') }}" class="nav-link @active('connector.*')">
                <i class="fa fa-plug w-5"></i> <span>Seedha Bill Link</span>
            </a>
            <a href="{{ route('connector.queue') }}" class="nav-link">
                <i class="fa fa-inbox w-5"></i> <span>Import Queue</span>
                @php $qCount = \App\Models\ConnectorImportQueue::where('tenant_id', auth()->user()->tenant_id)->where('status','quarantined')->count(); @endphp
                @if($qCount > 0)
                <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5">{{ $qCount }}</span>
                @endif
            </a>

            <div class="pt-3 pb-1">
                <p class="px-2 text-xs font-semibold text-navy-300 uppercase tracking-wider">AI Tools</p>
            </div>
            <a href="{{ route('ai.index') }}" class="nav-link @active('ai.*')">
                <i class="fa fa-robot w-5"></i> <span>AI Assistant</span>
            </a>
            {{-- AI / OCR is auto-provisioned on subscription — no per-user setup screen. --}}

            @if(auth()->user()->tenant?->isPramaan())
            <div class="pt-3 pb-1">
                <p class="px-2 text-xs font-semibold text-blue-300 uppercase tracking-wider">Pramaan — CA</p>
            </div>
            <a href="{{ route('pramaan.udin.index') }}" class="nav-link @active('pramaan.udin.*')">
                <i class="fa fa-certificate w-5"></i> <span>UDIN Register</span>
            </a>
            <a href="{{ route('pramaan.audit-reports.index') }}" class="nav-link @active('pramaan.audit-reports.*')">
                <i class="fa fa-scroll w-5"></i> <span>Audit Reports</span>
            </a>
            <a href="{{ route('pramaan.papers.index') }}" class="nav-link @active('pramaan.papers.*')">
                <i class="fa fa-folder-open w-5"></i> <span>Working Papers</span>
            </a>
            <a href="{{ route('pramaan.dsc.index') }}" class="nav-link @active('pramaan.dsc.*')">
                <i class="fa fa-signature w-5"></i> <span>DSC Vault</span>
            </a>
            <a href="{{ route('pramaan.calendar') }}" class="nav-link @active('pramaan.calendar')">
                <i class="fa fa-calendar-check w-5"></i> <span>Compliance Calendar</span>
            </a>
            <a href="{{ route('pramaan.notices.index') }}" class="nav-link @active('pramaan.notices.*')">
                <i class="fa fa-triangle-exclamation w-5"></i> <span>Notice Tracker</span>
            </a>
            <a href="{{ route('pramaan.clients') }}" class="nav-link @active('pramaan.clients')">
                <i class="fa fa-users w-5"></i> <span>All Clients</span>
            </a>
            @endif

            <div class="pt-3 pb-1">
                <p class="px-2 text-xs font-semibold text-navy-300 uppercase tracking-wider">Tools</p>
            </div>
            <a href="{{ route('accounting.tally.index') }}" class="nav-link">
                <i class="fa fa-file-import w-5"></i> <span>Tally Migration</span>
            </a>
        </nav>

    </aside>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        {{-- Top bar --}}
        <header class="h-16 bg-white border-b border-gray-200 flex items-center gap-3 px-4 lg:px-6">
            <button @click="sidebarOpen=true" class="lg:hidden text-gray-500 hover:text-gray-700 shrink-0">
                <i class="fa fa-bars text-xl"></i>
            </button>
            <h1 class="text-lg font-semibold text-gray-900 truncate shrink-0 hidden md:block">@yield('page-title', 'Dashboard')</h1>

            {{-- Global search --}}
            <div class="flex-1 max-w-xl mx-auto relative" x-data="globalSearch" @click.outside="open=false" @keydown.escape="open=false">
                <form @submit.prevent="go()" role="search">
                    <div class="relative">
                        <i class="fa fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" x-model="q" x-ref="input"
                               @input.debounce.250ms="fetchSuggest()" @focus="if(hasResults())open=true"
                               @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
                               placeholder="Search invoice #, GSTIN, client, phone…"
                               autocomplete="off"
                               class="w-full pl-9 pr-16 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-navy-400 focus:ring-1 focus:ring-navy-200 outline-none">
                        <kbd class="hidden sm:flex absolute right-3 top-1/2 -translate-y-1/2 items-center gap-0.5 text-[10px] text-gray-400 border border-gray-200 rounded px-1.5 py-0.5 bg-white">Ctrl K</kbd>
                    </div>
                </form>

                {{-- Suggestions dropdown --}}
                <div x-show="open" x-cloak x-transition.origin.top
                     class="absolute z-50 mt-1.5 w-full bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden max-h-[70vh] overflow-y-auto">
                    <template x-if="loading">
                        <div class="px-4 py-6 text-center text-sm text-gray-400"><i class="fa fa-circle-notch fa-spin mr-2"></i>Searching…</div>
                    </template>
                    <template x-if="!loading && !hasResults() && q.trim().length >= 2">
                        <div class="px-4 py-6 text-center text-sm text-gray-400">No matches for “<span x-text="q.trim()"></span>”.</div>
                    </template>

                    <template x-if="!loading && results.invoices.length">
                        <div>
                            <p class="px-4 pt-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Invoices &amp; Bills</p>
                            <template x-for="(r, i) in results.invoices" :key="'inv'+i">
                                <a :href="r.url" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50">
                                    <span class="w-8 h-8 rounded-lg bg-navy-50 text-navy-600 flex items-center justify-center shrink-0"><i class="fa fa-file-invoice text-xs"></i></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-gray-900 truncate" x-text="r.number"></span>
                                        <span class="block text-xs text-gray-400 truncate" x-text="r.sub"></span>
                                    </span>
                                    <span class="text-sm font-medium text-gray-700 shrink-0" x-text="r.amount"></span>
                                </a>
                            </template>
                        </div>
                    </template>

                    <template x-if="!loading && results.parties.length">
                        <div>
                            <p class="px-4 pt-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Vendors &amp; Customers</p>
                            <template x-for="(r, i) in results.parties" :key="'party'+i">
                                <a :href="r.url" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50">
                                    <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                          :class="r.type==='customer' ? 'bg-teal-50 text-teal-600' : 'bg-amber-50 text-amber-600'"><i class="fa fa-user text-xs"></i></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-gray-900 truncate" x-text="r.name"></span>
                                        <span class="block text-xs text-gray-400 truncate font-mono" x-text="r.sub"></span>
                                    </span>
                                    <span class="text-[10px] uppercase tracking-wide text-gray-400 shrink-0" x-text="r.type"></span>
                                </a>
                            </template>
                        </div>
                    </template>

                    <template x-if="!loading && hasResults()">
                        <a :href="allUrl()" class="block px-4 py-2.5 text-center text-xs font-medium text-navy-600 hover:bg-gray-50 border-t border-gray-100">
                            See all results for “<span x-text="q.trim()"></span>”
                        </a>
                    </template>
                </div>
            </div>

            {{-- AI credits pie — click opens a hanging popup (no page load) --}}
            @php
                $__t = auth()->user()->tenant;
                $__used = $__t?->aiCreditsUsed() ?? 0;
                $__unl = $__t?->aiCreditsUnlimited() ?? false;
                $__lim = $__unl ? 0 : ($__t?->aiCreditLimit() ?? 0);
                $__rem = $__t?->aiCreditsRemaining() ?? 0;
                $__pct = $__lim > 0 ? min(100, round($__used / $__lim * 100)) : 0;
                $__c = 2 * M_PI * 10; $__d = $__c * $__pct / 100;
                $__ring = $__pct >= 90 ? '#dc2626' : ($__pct >= 70 ? '#f59e0b' : '#4ade80');
                $__byType = $__unl ? collect() : \App\Models\AiUsage::where('tenant_id', $__t?->id)->where('billable', true)
                    ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
                    ->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type');
                $__typeLabels = ['extraction' => 'Invoice scans', 'assistant' => 'AI questions', 'nl_query' => 'NL queries', 'account_coding' => 'Auto-coding', 'anomaly' => 'Anomaly checks'];
            @endphp
            <div class="relative hidden sm:block shrink-0" x-data="{ creditsOpen: false }" @click.outside="creditsOpen = false" @keydown.escape="creditsOpen = false">
                <button type="button" @click="creditsOpen = !creditsOpen" title="AI credits"
                        class="flex items-center gap-1.5 text-gray-600 hover:text-navy-700">
                    @if($__unl)
                        <i class="fa fa-infinity text-navy-500"></i><span class="text-xs font-medium hidden md:inline">Credits</span>
                    @else
                        <svg viewBox="0 0 24 24" class="w-6 h-6 -rotate-90">
                            <circle cx="12" cy="12" r="10" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                            <circle cx="12" cy="12" r="10" fill="none" stroke="{{ $__ring }}" stroke-width="3" stroke-linecap="round" stroke-dasharray="{{ $__d }} {{ $__c }}"/>
                        </svg>
                        <span class="text-xs font-medium">{{ $__used }}<span class="text-gray-400">/{{ $__lim }}</span></span>
                    @endif
                    <i class="fa fa-chevron-down text-[9px] text-gray-400"></i>
                </button>
                <div x-show="creditsOpen" x-transition x-cloak
                     class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden">
                    <div class="px-4 py-3 bg-navy-600 text-white">
                        <p class="text-[11px] uppercase tracking-wider text-navy-200">AI credits · {{ now()->format('M Y') }}</p>
                        @if($__unl)
                            <p class="text-lg font-bold"><i class="fa fa-infinity mr-1"></i>Unlimited</p>
                        @else
                            <p class="text-lg font-bold">{{ $__used }} <span class="text-navy-200 text-sm font-normal">of {{ $__lim }} used</span></p>
                            <div class="mt-2 h-1.5 rounded-full bg-navy-800/50 overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ $__pct }}%; background: {{ $__ring }}"></div>
                            </div>
                            <p class="text-[11px] text-navy-200 mt-1">{{ $__rem }} credits left · resets on the 1st</p>
                        @endif
                    </div>
                    @unless($__unl)
                    <div class="p-3">
                        <p class="text-[11px] uppercase tracking-wider text-gray-400 mb-1.5">This month by type</p>
                        @forelse($__byType as $__type => $__count)
                        <div class="flex items-center justify-between text-sm py-1">
                            <span class="text-gray-600">{{ $__typeLabels[$__type] ?? ucwords(str_replace('_', ' ', $__type)) }}</span>
                            <span class="font-medium text-gray-800">{{ $__count }}</span>
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 py-1">No AI actions yet this month.</p>
                        @endforelse
                    </div>
                    @endunless
                    <div class="px-3 py-2.5 border-t border-gray-100 flex items-center justify-between">
                        <a href="{{ route('ai.credits') }}" class="text-sm text-navy-600 font-medium hover:underline">Full history →</a>
                        <span class="text-[11px] text-gray-400">1 credit = 1 AI action</span>
                    </div>
                </div>
            </div>

            <span class="hidden lg:block text-sm text-gray-500 shrink-0">{{ now()->format('d M Y') }}</span>

            {{-- User / avatar menu (moved off the sidebar) --}}
            <div class="relative shrink-0" x-data="{ userMenu: false }" @click.outside="userMenu = false" @keydown.escape="userMenu = false">
                <button @click="userMenu = !userMenu" class="flex items-center gap-2 rounded-lg hover:bg-gray-50 px-1.5 py-1">
                    <div class="w-8 h-8 rounded-full bg-navy-600 text-white flex items-center justify-center text-sm font-bold shrink-0">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    <div class="hidden md:block text-left leading-tight max-w-[9rem]">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->tenant?->name }}</p>
                    </div>
                    <i class="fa fa-chevron-down text-xs text-gray-400"></i>
                </button>
                <div x-show="userMenu" x-cloak x-transition class="absolute right-0 mt-2 w-56 bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden z-50">
                    <div class="px-4 py-3 border-b border-gray-100 md:hidden">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->tenant?->name }}</p>
                    </div>
                    @php $__companies = auth()->user()->companies()->orderBy('name')->get(); @endphp
                    @if($__companies->count() > 1 || auth()->user()->canAddCompany())
                    <div class="px-3 py-2 border-b border-gray-100">
                        <p class="text-[11px] uppercase tracking-wider text-gray-400 px-1 mb-1">Companies</p>
                        <div class="max-h-52 overflow-y-auto">
                        @foreach($__companies as $__co)
                            @if($__co->id === auth()->user()->tenant_id)
                            <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-sm bg-navy-50 text-navy-700 font-medium">
                                <i class="fa fa-circle-check text-navy-500 w-4"></i><span class="truncate">{{ $__co->name }}</span>
                            </div>
                            @else
                            <form method="POST" action="{{ route('companies.switch', $__co) }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-sm text-left text-gray-700 hover:bg-gray-50">
                                    <i class="fa fa-building text-gray-300 w-4"></i><span class="truncate">{{ $__co->name }}</span>
                                </button>
                            </form>
                            @endif
                        @endforeach
                        </div>
                        <div class="flex items-center justify-between px-1 mt-1">
                            @if(auth()->user()->canAddCompany())
                            <a href="{{ route('companies.create') }}" class="text-xs text-navy-600 font-medium hover:underline"><i class="fa fa-plus mr-1"></i>Add company</a>
                            @else
                            <span class="text-[11px] text-gray-400">Plan limit reached</span>
                            @endif
                            <a href="{{ route('companies.index') }}" class="text-xs text-gray-400 hover:text-gray-600">Manage</a>
                        </div>
                    </div>
                    @endif
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50"><i class="fa fa-gear w-4 text-gray-400"></i>Settings</a>
                    <a href="{{ route('marketing.help') }}" target="_blank" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50"><i class="fa fa-circle-question w-4 text-gray-400"></i>Help &amp; Docs</a>
                    <a href="https://prabhassaas.in" target="_blank" rel="noopener" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50"><i class="fa fa-house w-4 text-gray-400"></i>Prabhas SaaS Home<i class="fa fa-arrow-up-right-from-square ml-auto text-[10px] text-gray-300"></i></a>
                    <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50"><i class="fa fa-right-from-bracket w-4"></i>Log out</button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Impersonation banner --}}
        @if(session('impersonating'))
        <div class="bg-amber-400 px-4 lg:px-6 py-2 flex items-center justify-between flex-shrink-0">
            <span class="text-amber-950 text-sm font-medium">
                <i class="fa fa-user-secret mr-2"></i>
                Admin mode: impersonating <strong>{{ auth()->user()->name }}</strong>
                ({{ auth()->user()->email }})
            </span>
            <form method="POST" action="{{ route('admin.stop-impersonating') }}">
                @csrf
                <button type="submit" class="text-amber-900 font-bold text-sm hover:underline">
                    Stop &amp; Return to Admin
                </button>
            </form>
        </div>
        @endif

        {{-- Flash messages --}}
        <div class="px-4 lg:px-6 pt-4">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center">
                    <i class="fa fa-circle-check mr-2 text-green-500"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center">
                    <i class="fa fa-circle-xmark mr-2 text-red-500"></i> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto px-4 lg:px-6 pb-8">
            @yield('content')
        </main>
    </div>
</div>

{{-- ── Splash Screen (first load only) ──────────────────────────── --}}
<div id="lekhya-splash" style="position:fixed;inset:0;z-index:9999;background:#1B2A4A;display:flex;flex-direction:column;align-items:center;justify-content:center;transition:opacity 0.6s ease">
  <div style="text-align:center">
    <div style="width:72px;height:72px;background:#2e5a94;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 8px 32px rgba(0,0,0,0.4)">
      <img src="{{ asset('logo-mark.svg') }}" alt="Lekhya" style="width:44px;height:44px">
    </div>
    <p style="color:#fff;font-size:1.5rem;font-weight:700;letter-spacing:-0.01em;margin:0 0 4px">Lekhya</p>
    <p style="color:#7fa0c9;font-size:0.85rem;margin:0 0 32px">AI-powered GST ERP</p>
    <div style="width:200px;height:3px;background:rgba(255,255,255,0.1);border-radius:99px;overflow:hidden;margin:0 auto">
      <div id="splash-bar" style="height:100%;width:0%;background:#4ade80;border-radius:99px;transition:width 0.05s linear"></div>
    </div>
  </div>
</div>

{{-- ── Page transition progress bar ─────────────────────────────── --}}
<div id="page-progress" style="position:fixed;top:0;left:0;right:0;height:3px;z-index:9998;pointer-events:none;opacity:0;transition:opacity 0.2s">
  <div id="page-progress-bar" style="height:100%;width:0%;background:#4ade80;transition:width 0.3s ease;box-shadow:0 0 8px #4ade80"></div>
</div>

<style>
.nav-link { display:flex; align-items:center; gap:0.625rem; padding:0.5rem 0.75rem; border-radius:0.5rem; font-size:0.875rem; color:#b3c4df; transition:background-color 0.15s, color 0.15s; }
.nav-link:hover, .nav-link.active { background-color:rgba(255,255,255,0.1); color:#fff; }
[x-cloak] { display:none; }
/* Sidebar scrollbar — themed to the navy panel, not default white/grey */
#sidebar-nav { scrollbar-width: thin; scrollbar-color: #2e5a94 transparent; }
#sidebar-nav::-webkit-scrollbar { width: 8px; }
#sidebar-nav::-webkit-scrollbar-track { background: transparent; }
#sidebar-nav::-webkit-scrollbar-thumb { background: #2e5a94; border-radius: 99px; }
#sidebar-nav::-webkit-scrollbar-thumb:hover { background: #4f7ab0; }
</style>

<script>
(function() {
  // ── Sidebar scroll persistence (keep menu position across page loads) ──
  var navEl = document.getElementById('sidebar-nav');
  if (navEl) {
    var savedScroll = sessionStorage.getItem('lekhya_nav_scroll');
    if (savedScroll !== null) navEl.scrollTop = parseInt(savedScroll, 10) || 0;
    navEl.addEventListener('scroll', function() {
      sessionStorage.setItem('lekhya_nav_scroll', navEl.scrollTop);
    }, { passive: true });
  }

  var splash = document.getElementById('lekhya-splash');
  var bar    = document.getElementById('splash-bar');
  var shown  = sessionStorage.getItem('lekhya_splash_shown');

  if (shown) {
    // Already shown this session — hide immediately
    splash.style.display = 'none';
  } else {
    // Animate progress bar over 5 seconds
    var pct = 0;
    var tick = setInterval(function() {
      pct = Math.min(pct + 2, 100);
      bar.style.width = pct + '%';
      if (pct >= 100) {
        clearInterval(tick);
        setTimeout(function() {
          splash.style.opacity = '0';
          setTimeout(function() { splash.style.display = 'none'; }, 650);
        }, 200);
      }
    }, 100); // 100ms × 50 steps = 5 seconds
    sessionStorage.setItem('lekhya_splash_shown', '1');
  }

  // Page transition progress bar
  var prog    = document.getElementById('page-progress');
  var progBar = document.getElementById('page-progress-bar');
  var progInterval = null;

  function startProgress() {
    prog.style.opacity = '1';
    progBar.style.width = '0%';
    var w = 0;
    progInterval = setInterval(function() {
      w = Math.min(w + (Math.random() * 8 + 2), 85);
      progBar.style.width = w + '%';
    }, 200);
  }
  function finishProgress() {
    clearInterval(progInterval);
    progBar.style.width = '100%';
    setTimeout(function() {
      prog.style.opacity = '0';
      setTimeout(function() { progBar.style.width = '0%'; }, 300);
    }, 200);
  }

  document.addEventListener('click', function(e) {
    var a = e.target.closest('a');
    if (a && a.href && a.target !== '_blank' && a.href.startsWith(window.location.origin) && !a.href.includes('#')) {
      startProgress();
    }
  });
  document.addEventListener('submit', function() { startProgress(); });
  window.addEventListener('pageshow', function() { finishProgress(); });
})();
</script>

{{-- ── Global search component ───────────────────────────────────── --}}
<script>
document.addEventListener('alpine:init', function () {
  Alpine.data('globalSearch', function () {
    return {
      q: '', open: false, loading: false,
      results: { parties: [], invoices: [] },
      hasResults() { return this.results.parties.length > 0 || this.results.invoices.length > 0; },
      fetchSuggest() {
        var term = this.q.trim();
        if (term.length < 2) { this.results = { parties: [], invoices: [] }; this.open = false; return; }
        this.loading = true; this.open = true;
        fetch(@js(route('search.suggest')) + '?q=' + encodeURIComponent(term), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (r) { return r.json(); })
          .then((d) => { this.results = { parties: d.parties || [], invoices: d.invoices || [] }; this.loading = false; })
          .catch(() => { this.loading = false; });
      },
      go() { var t = this.q.trim(); if (t.length) window.location = this.allUrl(); },
      allUrl() { return @js(route('search')) + '?q=' + encodeURIComponent(this.q.trim()); },
      move(dir) {
        var links = Array.from(this.$el.querySelectorAll('a[href]'));
        if (!links.length) return;
        var idx = links.indexOf(document.activeElement);
        var next = idx + dir;
        if (next < 0) { this.$refs.input.focus(); return; }
        if (next >= links.length) next = links.length - 1;
        links[next].focus();
      },
      init() {
        var self = this;
        window.addEventListener('keydown', function (e) {
          if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault(); self.$refs.input.focus(); self.$refs.input.select();
          }
        });
      },
    };
  });
});
</script>

@include('partials.calculator')
@include('partials.assistant')

@stack('scripts')
</body>
</html>
