<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Lekhya') — AI-powered GST ERP for India</title>
    <meta name="description" content="@yield('meta-desc', 'Lekhya is India\'s seedha-saadha AI accounting ERP. GST-compliant, double-entry, cloud-based. Free 14-day trial.')">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzMiAzMiI+CiAgPHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiByeD0iNyIgZmlsbD0iIzFCMkE0QSIvPgogIDx0ZXh0IHg9IjE2IiB5PSIyMyIgZm9udC1mYW1pbHk9InNlcmlmIiBmb250LXNpemU9IjIwIiBmb250LXdlaWdodD0iYm9sZCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPuCksjwvdGV4dD4KPC9zdmc+Cg==">
    <link rel="alternate icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { navy: { 50:'#f0f3f8', 600:'#1B2A4A', 700:'#162240', 900:'#0c1226' } } } } }</script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @stack('styles')
</head>
<body class="bg-white font-sans" x-data="{ mobileMenu: false }">

<nav class="sticky top-0 z-50 bg-white border-b border-gray-100 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-3">
                <a href="https://prabhassaas.in" class="hidden md:flex items-center space-x-1 text-xs text-gray-400 hover:text-navy-600 transition border-r border-gray-200 pr-3 mr-1">
                    <i class="fa fa-house text-xs"></i>
                    <span>Prabhas SaaS</span>
                </a>
                <a href="{{ route('marketing.home') }}" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-navy-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">ल</span>
                    </div>
                    <span class="text-navy-600 font-bold text-xl">Lekhya</span>
                </a>
            </div>

            <div class="hidden md:flex items-center space-x-8">
                <a href="{{ route('marketing.features') }}" class="text-gray-600 hover:text-navy-600 text-sm font-medium">Features</a>
                <a href="{{ route('marketing.pricing') }}" class="text-gray-600 hover:text-navy-600 text-sm font-medium">Pricing</a>
                <a href="{{ route('marketing.connector') }}" class="text-gray-600 hover:text-navy-600 text-sm font-medium">Seedha Bill</a>
                <a href="{{ route('marketing.flows') }}" class="text-gray-600 hover:text-navy-600 text-sm font-medium">How it Works</a>
                <a href="{{ route('marketing.help') }}" class="text-gray-600 hover:text-navy-600 text-sm font-medium">Help</a>
            </div>

            <div class="hidden md:flex items-center space-x-3">
                @guest
                <a href="{{ route('login') }}" class="text-navy-600 hover:text-navy-700 text-sm font-medium px-4 py-2">Sign In</a>
                <a href="{{ route('register') }}" class="text-sm font-semibold px-4 py-2 rounded-lg transition" style="background:#f2a024;color:#1b2a4a">Start Free Trial</a>
                @else

                {{-- App Switcher --}}
                <div x-data="{ appMenu: false }" class="relative">
                    <button @click="appMenu=!appMenu" @click.away="appMenu=false"
                            class="flex items-center space-x-1.5 text-navy-600 text-sm font-medium px-3 py-2 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition">
                        <div class="w-5 h-5 bg-navy-600 rounded flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-bold text-xs">ल</span>
                        </div>
                        <span>Lekhya</span>
                        <i class="fa fa-chevron-down text-xs text-gray-400 transition-transform duration-150" :class="appMenu ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="appMenu" x-cloak
                         class="absolute right-0 mt-2 w-52 bg-white rounded-xl border border-gray-100 shadow-lg py-1 z-50">
                        <div class="px-4 py-2 text-xs text-gray-400 font-semibold uppercase tracking-wider">Your Apps</div>
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center justify-between px-4 py-2 text-sm text-navy-600 font-medium hover:bg-gray-50">
                            <span>Lekhya ERP</span>
                            <i class="fa fa-check text-navy-600 text-xs"></i>
                        </a>
                        <a href="{{ config('services.prabhas.seedhabill_url', 'https://seedhabill.in') }}" target="_blank" rel="noopener"
                           class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <span>SeedhaBill</span>
                            <i class="fa fa-arrow-up-right-from-square text-gray-400 text-xs"></i>
                        </a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="{{ config('services.prabhas.accounts_url', 'https://accounts.prabhas.in') }}" target="_blank" rel="noopener"
                           class="flex items-center space-x-2 px-4 py-2 text-sm text-gray-500 hover:bg-gray-50 hover:text-gray-700">
                            <i class="fa fa-plus text-xs"></i>
                            <span>Add App</span>
                        </a>
                    </div>
                </div>

                {{-- User avatar + dropdown --}}
                <div x-data="{ userMenu: false }" class="relative">
                    <button @click="userMenu=!userMenu" @click.away="userMenu=false"
                            class="flex items-center space-x-2 px-2 py-1.5 rounded-lg hover:bg-gray-50 transition">
                        <div class="w-8 h-8 rounded-full bg-navy-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <span class="text-sm text-gray-700 font-medium max-w-[7rem] truncate">
                            {{ explode(' ', auth()->user()->name)[0] }}
                        </span>
                        <i class="fa fa-chevron-down text-xs text-gray-400"></i>
                    </button>
                    <div x-show="userMenu" x-cloak
                         class="absolute right-0 mt-2 w-48 bg-white rounded-xl border border-gray-100 shadow-lg py-1 z-50">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center space-x-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fa fa-gauge-high w-4 text-gray-400"></i><span>Dashboard</span>
                        </a>
                        <a href="{{ route('settings.index') }}"
                           class="flex items-center space-x-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fa fa-gear w-4 text-gray-400"></i><span>Settings</span>
                        </a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center space-x-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fa fa-right-from-bracket w-4"></i><span>Sign Out</span>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Open Dashboard CTA --}}
                <a href="{{ route('dashboard') }}"
                   class="bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    Open Dashboard
                </a>
                @endguest
            </div>

            <button @click="mobileMenu=!mobileMenu" class="md:hidden text-gray-600">
                <i class="fa fa-bars text-xl"></i>
            </button>
        </div>
    </div>

    <div x-show="mobileMenu" x-cloak class="md:hidden border-t border-gray-100 bg-white px-4 py-3 space-y-1">
        <a href="{{ route('marketing.features') }}" class="block py-2 text-gray-700 text-sm">Features</a>
        <a href="{{ route('marketing.pricing') }}" class="block py-2 text-gray-700 text-sm">Pricing</a>
        <a href="{{ route('marketing.connector') }}" class="block py-2 text-gray-700 text-sm">Seedha Bill</a>
        <a href="{{ route('marketing.flows') }}" class="block py-2 text-gray-700 text-sm">How it Works</a>
        <a href="{{ route('marketing.help') }}" class="block py-2 text-gray-700 text-sm">Help</a>
        @guest
        <div class="pt-2 border-t border-gray-100 space-y-2">
            <a href="https://prabhassaas.in" class="block py-2 text-gray-400 text-xs">← Prabhas SaaS</a>
            <a href="{{ route('login') }}" class="block py-2 text-gray-700 text-sm">Sign In</a>
            <a href="{{ route('register') }}" class="block py-2 font-semibold text-sm" style="color:#f2a024">Start Free Trial</a>
        </div>
        @else
        <div class="pt-2 border-t border-gray-100">
            <div class="flex items-center space-x-2 py-2">
                <div class="w-8 h-8 rounded-full bg-navy-600 flex items-center justify-center text-white text-xs font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <a href="{{ route('dashboard') }}" class="block py-2 text-navy-600 font-semibold text-sm">Open Dashboard</a>
            <a href="{{ config('services.prabhas.seedhabill_url', 'https://seedhabill.in') }}" target="_blank" rel="noopener"
               class="block py-2 text-gray-700 text-sm">SeedhaBill <i class="fa fa-arrow-up-right-from-square text-xs ml-0.5"></i></a>
            <a href="{{ config('services.prabhas.accounts_url', 'https://accounts.prabhas.in') }}" target="_blank" rel="noopener"
               class="block py-2 text-gray-700 text-sm">Manage Apps</a>
            <div class="pt-1 border-t border-gray-100 mt-1">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="py-2 text-red-600 font-medium text-sm text-left">Sign Out</button>
                </form>
            </div>
        </div>
        @endguest
    </div>
</nav>

@yield('content')

<footer class="bg-navy-900 text-gray-300 mt-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center space-x-2 mb-4">
                    <div class="w-8 h-8 bg-navy-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm" style="font-family:serif">ल</span>
                    </div>
                    <span class="text-white font-bold text-xl">Lekhya</span>
                </div>
                <p class="text-sm text-gray-400 mb-5">Seedha-saadha GST accounting for India. Part of Prabhas SaaS — one login, every app.</p>
                <div class="flex items-center space-x-2 bg-white bg-opacity-5 rounded-xl px-3 py-2 border border-white border-opacity-10 w-fit">
                    <img src="/prabhas-logo.svg" alt="Prabhas SaaS" class="h-7 w-7 rounded object-contain bg-white p-0.5">
                    <div>
                        <p class="text-white text-xs font-bold leading-none">PRABHAS</p>
                        <p class="text-gray-400 text-xs leading-none">SaaS</p>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-white font-semibold mb-4">Product</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('marketing.features') }}" class="hover:text-white">Features</a></li>
                    <li><a href="{{ route('marketing.pricing') }}" class="hover:text-white">Pricing</a></li>
                    <li><a href="{{ route('marketing.flows') }}" class="hover:text-white">How it Works</a></li>
                    <li><a href="{{ route('marketing.connector') }}" class="hover:text-white">Seedha Bill Link</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-semibold mb-4">Resources</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('marketing.help') }}" class="hover:text-white">Help Center</a></li>
                    <li><a href="{{ route('marketing.help', ['topic' => 'tally-migration']) }}" class="hover:text-white">Tally Migration Guide</a></li>
                    <li><a href="{{ route('marketing.help', ['topic' => 'gst-api']) }}" class="hover:text-white">GST API Guide</a></li>
                    <li><a href="{{ route('marketing.help', ['topic' => 'local-llm']) }}" class="hover:text-white">AI / LLM Setup</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-semibold mb-4">Company</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('marketing.about') }}" class="hover:text-white">About</a></li>
                    <li><a href="{{ route('marketing.contact') }}" class="hover:text-white">Contact</a></li>
                    <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                    <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-12 pt-8 border-t border-gray-800 flex flex-col sm:flex-row justify-between items-center">
            <p class="text-sm text-gray-500">© {{ date('Y') }} Prabhas SaaS. All rights reserved.</p>
            <p class="text-xs text-gray-600 mt-2 sm:mt-0">Made with ♥ in India 🇮🇳</p>
        </div>
    </div>
</footer>

<style>[x-cloak] { display:none; }</style>
@stack('scripts')
</body>
</html>
