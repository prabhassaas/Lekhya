<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Lekhya') — Lekhya AI ERP</title>
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
                    <span class="text-white font-bold text-sm">ल</span>
                </div>
                <div>
                    <span class="text-white font-bold text-lg">Lekhya</span>
                    @if(auth()->user()->tenant?->isPramaan())
                        <span class="ml-1 text-xs text-blue-300">Pramaan</span>
                    @endif
                </div>
            </a>
        </div>

        {{-- Trial badge --}}
        @php $entitlement = auth()->user()->tenant?->entitlements()->where('app','lekhya')->where('is_active',true)->first(); @endphp
        @if($entitlement && $entitlement->trial_ends_at && $entitlement->trial_ends_at->isFuture())
        <div class="mx-3 mt-3 px-3 py-2 bg-amber-500 bg-opacity-20 rounded-lg border border-amber-400">
            <p class="text-amber-200 text-xs font-medium">Trial: {{ $entitlement->trial_ends_at->diffForHumans() }} left</p>
        </div>
        @endif

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
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
            <a href="{{ route('accounting.journals.index') }}" class="nav-link @active('accounting.journals*')">
                <i class="fa fa-book w-5"></i> <span>Journal Vouchers</span>
            </a>
            <a href="{{ route('accounting.accounts.index') }}" class="nav-link @active('accounting.accounts*')">
                <i class="fa fa-sitemap w-5"></i> <span>Chart of Accounts</span>
            </a>

            <div class="pt-3 pb-1">
                <p class="px-2 text-xs font-semibold text-navy-300 uppercase tracking-wider">Reports</p>
            </div>
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
            <a href="{{ route('gst.dashboard') }}" class="nav-link @active('gst.*')">
                <i class="fa fa-landmark w-5"></i> <span>GST Dashboard</span>
            </a>
            <a href="{{ route('gst.gstr1') }}" class="nav-link">
                <i class="fa fa-file-text w-5"></i> <span>GSTR-1</span>
            </a>
            <a href="{{ route('gst.gstr3b') }}" class="nav-link">
                <i class="fa fa-file-text w-5"></i> <span>GSTR-3B</span>
            </a>
            <a href="{{ route('gst.gstr2b') }}" class="nav-link">
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

            @if(auth()->user()->tenant?->isPramaan())
            <div class="pt-3 pb-1">
                <p class="px-2 text-xs font-semibold text-blue-300 uppercase tracking-wider">Pramaan — CA</p>
            </div>
            <a href="{{ route('pramaan.udin.index') }}" class="nav-link @active('pramaan.*')">
                <i class="fa fa-certificate w-5"></i> <span>UDIN Register</span>
            </a>
            <a href="{{ route('pramaan.audit-reports.index') }}" class="nav-link">
                <i class="fa fa-scroll w-5"></i> <span>Audit Reports</span>
            </a>
            <a href="{{ route('pramaan.calendar') }}" class="nav-link">
                <i class="fa fa-calendar-check w-5"></i> <span>Compliance Calendar</span>
            </a>
            <a href="{{ route('pramaan.clients') }}" class="nav-link">
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

        {{-- Bottom: User + Settings --}}
        <div class="border-t border-navy-500 p-3">
            <a href="{{ route('settings.index') }}" class="nav-link">
                <i class="fa fa-gear w-5"></i> <span>Settings</span>
            </a>
            <a href="{{ route('marketing.help') }}" target="_blank" class="nav-link">
                <i class="fa fa-circle-question w-5"></i> <span>Help & Docs</span>
            </a>
            <div class="mt-2 flex items-center space-x-2 px-2 py-2 rounded-lg bg-navy-700">
                <div class="w-8 h-8 rounded-full bg-blue-400 flex items-center justify-center text-white text-sm font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                    <p class="text-navy-300 text-xs truncate">{{ auth()->user()->tenant?->name }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-navy-300 hover:text-white">
                        <i class="fa fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        {{-- Top bar --}}
        <header class="h-16 bg-white border-b border-gray-200 flex items-center px-4 lg:px-6 space-x-4">
            <button @click="sidebarOpen=true" class="lg:hidden text-gray-500 hover:text-gray-700">
                <i class="fa fa-bars text-xl"></i>
            </button>
            <div class="flex-1">
                <h1 class="text-lg font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center space-x-3">
                <span class="hidden sm:block text-sm text-gray-500">{{ now()->format('d M Y') }}</span>
            </div>
        </header>

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

<style>
.nav-link { display:flex; align-items:center; gap:0.625rem; padding:0.5rem 0.75rem; border-radius:0.5rem; font-size:0.875rem; color:#b3c4df; transition:background-color 0.15s, color 0.15s; }
.nav-link:hover, .nav-link.active { background-color:rgba(255,255,255,0.1); color:#fff; }
[x-cloak] { display:none; }
</style>

@stack('scripts')
</body>
</html>
