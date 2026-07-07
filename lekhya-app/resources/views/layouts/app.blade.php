<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Lekhya') — Lekhya AI ERP</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzMiAzMiI+CiAgPHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiByeD0iNyIgZmlsbD0iIzFCMkE0QSIvPgogIDx0ZXh0IHg9IjE2IiB5PSIyMyIgZm9udC1mYW1pbHk9InNlcmlmIiBmb250LXNpemU9IjIwIiBmb250LXdlaWdodD0iYm9sZCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPuCksjwvdGV4dD4KPC9zdmc+Cg==">
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
            <a href="https://prabhassaas.in" target="_blank" rel="noopener"
               class="nav-link mb-1" style="color:#7fa0c9;font-size:0.78rem;letter-spacing:.01em">
                <i class="fa fa-house w-5" style="font-size:0.75rem"></i>
                <span>Prabhas SaaS Home</span>
                <i class="fa fa-arrow-up-right-from-square ml-auto" style="font-size:0.65rem;opacity:.5"></i>
            </a>
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
      <span style="color:#fff;font-size:36px;font-weight:700;font-family:serif">ल</span>
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
</style>

<script>
(function() {
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

@stack('scripts')
</body>
</html>
