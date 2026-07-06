<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') — Prabhas Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 600:'#1B2A4A', 700:'#162240', 900:'#0c1226' }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @stack('styles')
</head>
<body class="h-full bg-gray-950 font-sans" x-data="{}">

<div class="flex h-full">

    {{-- ── Sidebar ─────────────────────────────────────────────────────────── --}}
    <aside class="w-64 flex flex-col flex-shrink-0 bg-gray-900 border-r border-gray-800">

        {{-- Logo / brand --}}
        <div class="flex items-center h-16 px-5 border-b border-gray-800">
            <div class="flex items-center space-x-3">
                <img src="/prabhas-logo.svg" alt="Prabhas SaaS" class="h-9 w-9 rounded-xl object-contain bg-white p-0.5">
                <div>
                    <p class="text-white font-bold text-sm leading-none">Prabhas SaaS</p>
                    <p class="text-violet-400 text-xs mt-0.5">Super Admin</p>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-0.5">
            <a href="{{ route('admin.dashboard') }}"
               class="admin-nav {{ request()->routeIs('admin.dashboard') ? 'admin-nav-active' : '' }}">
                <i class="fa fa-gauge-high w-5 text-center"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('admin.tenants') }}"
               class="admin-nav {{ request()->routeIs('admin.tenants*') ? 'admin-nav-active' : '' }}">
                <i class="fa fa-building w-5 text-center"></i>
                <span>All Tenants</span>
            </a>
            <a href="{{ route('admin.subscriptions') }}"
               class="admin-nav {{ request()->routeIs('admin.subscriptions*') ? 'admin-nav-active' : '' }}">
                <i class="fa fa-credit-card w-5 text-center"></i>
                <span>Subscriptions</span>
            </a>

            <div class="pt-4 pb-1">
                <p class="px-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">Config</p>
            </div>
            <a href="{{ route('admin.feature-flags') }}"
               class="admin-nav {{ request()->routeIs('admin.feature-flags*') ? 'admin-nav-active' : '' }}">
                <i class="fa fa-flag w-5 text-center"></i>
                <span>Feature Flags</span>
            </a>
            <a href="{{ route('admin.audit-log') }}"
               class="admin-nav {{ request()->routeIs('admin.audit-log*') ? 'admin-nav-active' : '' }}">
                <i class="fa fa-scroll w-5 text-center"></i>
                <span>Audit Log</span>
            </a>
            <a href="{{ route('admin.tenants') }}?impersonate=1"
               class="admin-nav {{ request()->routeIs('admin.impersonate*') ? 'admin-nav-active' : '' }}">
                <i class="fa fa-user-secret w-5 text-center"></i>
                <span>Impersonate</span>
            </a>
        </nav>

        {{-- Admin user info + sign out --}}
        <div class="border-t border-gray-800 p-3">
            <div class="flex items-center space-x-2.5 px-2 py-2.5 rounded-lg bg-gray-800">
                <div class="w-8 h-8 rounded-full bg-violet-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                    <p class="text-gray-400 text-xs truncate">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Sign Out" class="text-gray-500 hover:text-white transition ml-1">
                        <i class="fa fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ── Main area ───────────────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top header --}}
        <header class="h-16 bg-gray-900 border-b border-gray-800 flex items-center px-6 space-x-4 flex-shrink-0">
            <div class="flex-1">
                <h1 class="text-base font-semibold text-white">@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center space-x-3">
                <span class="hidden sm:block text-sm text-gray-500">{{ now()->format('d M Y') }}</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-violet-900 bg-opacity-60 text-violet-300 text-xs font-medium rounded-full">
                    <i class="fa fa-shield-halved text-xs"></i> Super Admin
                </span>
                <a href="{{ route('dashboard') }}" target="_blank"
                   class="text-gray-500 hover:text-gray-300 text-sm transition" title="Open Lekhya App">
                    <i class="fa fa-arrow-up-right-from-square"></i>
                </a>
            </div>
        </header>

        {{-- Impersonation banner --}}
        @if(session('impersonating'))
        <div class="bg-amber-500 px-6 py-2 flex items-center justify-between flex-shrink-0">
            <span class="text-amber-950 text-sm font-medium">
                <i class="fa fa-user-secret mr-2"></i>
                Impersonating: <strong>{{ auth()->user()->name }}</strong>
                ({{ auth()->user()->email }})
            </span>
            <form method="POST" action="{{ route('admin.stop-impersonating') }}">
                @csrf
                <button type="submit" class="text-amber-900 font-bold text-sm hover:text-amber-950 underline">
                    Stop Impersonating
                </button>
            </form>
        </div>
        @endif

        {{-- Flash messages --}}
        @if(session('success') || session('error'))
        <div class="px-6 pt-4 flex-shrink-0">
            @if(session('success'))
            <div class="mb-3 p-3 bg-green-900 bg-opacity-50 border border-green-700 text-green-300 rounded-lg text-sm flex items-center gap-2">
                <i class="fa fa-circle-check text-green-400"></i> {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="mb-3 p-3 bg-red-900 bg-opacity-50 border border-red-700 text-red-300 rounded-lg text-sm flex items-center gap-2">
                <i class="fa fa-circle-xmark text-red-400"></i> {{ session('error') }}
            </div>
            @endif
        </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto px-6 py-6">
            @yield('content')
        </main>
    </div>
</div>

<style>
.admin-nav {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
    transition: background-color 0.15s, color 0.15s;
}
.admin-nav:hover   { background-color: rgba(139,92,246,0.12); color: #c4b5fd; }
.admin-nav-active  { background-color: rgba(139,92,246,0.18); color: #c4b5fd; }
[x-cloak]          { display: none; }
</style>

@stack('scripts')
</body>
</html>
