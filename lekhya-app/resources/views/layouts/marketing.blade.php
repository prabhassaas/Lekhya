<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Lekhya') — AI-powered GST ERP for India</title>
    <meta name="description" content="@yield('meta-desc', 'Lekhya is India\'s seedha-saadha AI accounting ERP. GST-compliant, double-entry, cloud-based. Free 14-day trial.')">
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
            <a href="{{ route('marketing.home') }}" class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-navy-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm">ल</span>
                </div>
                <span class="text-navy-600 font-bold text-xl">Lekhya</span>
            </a>

            <div class="hidden md:flex items-center space-x-8">
                <a href="{{ route('marketing.features') }}" class="text-gray-600 hover:text-navy-600 text-sm font-medium">Features</a>
                <a href="{{ route('marketing.pricing') }}" class="text-gray-600 hover:text-navy-600 text-sm font-medium">Pricing</a>
                <a href="{{ route('marketing.connector') }}" class="text-gray-600 hover:text-navy-600 text-sm font-medium">Seedha Bill</a>
                <a href="{{ route('marketing.flows') }}" class="text-gray-600 hover:text-navy-600 text-sm font-medium">How it Works</a>
                <a href="{{ route('marketing.help') }}" class="text-gray-600 hover:text-navy-600 text-sm font-medium">Help</a>
            </div>

            <div class="hidden md:flex items-center space-x-3">
                <a href="{{ route('login') }}" class="text-navy-600 hover:text-navy-700 text-sm font-medium px-4 py-2">Sign In</a>
                <a href="{{ route('register') }}" class="bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Start Free Trial</a>
            </div>

            <button @click="mobileMenu=!mobileMenu" class="md:hidden text-gray-600">
                <i class="fa fa-bars text-xl"></i>
            </button>
        </div>
    </div>

    <div x-show="mobileMenu" x-cloak class="md:hidden border-t border-gray-100 bg-white px-4 py-3 space-y-2">
        <a href="{{ route('marketing.features') }}" class="block py-2 text-gray-700">Features</a>
        <a href="{{ route('marketing.pricing') }}" class="block py-2 text-gray-700">Pricing</a>
        <a href="{{ route('marketing.connector') }}" class="block py-2 text-gray-700">Seedha Bill</a>
        <a href="{{ route('marketing.flows') }}" class="block py-2 text-gray-700">How it Works</a>
        <a href="{{ route('marketing.help') }}" class="block py-2 text-gray-700">Help</a>
        <a href="{{ route('login') }}" class="block py-2 text-gray-700">Sign In</a>
        <a href="{{ route('register') }}" class="block py-2 text-navy-600 font-semibold">Start Free Trial</a>
    </div>
</nav>

@yield('content')

<footer class="bg-navy-900 text-gray-300 mt-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center space-x-2 mb-4">
                    <div class="w-8 h-8 bg-blue-400 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">ल</span>
                    </div>
                    <span class="text-white font-bold text-xl">Lekhya</span>
                </div>
                <p class="text-sm text-gray-400 mb-4">Seedha-saadha GST accounting for India. Part of <strong class="text-gray-300">Prabhas SaaS</strong> — one login, every app.</p>
                <p class="text-xs text-gray-500">FOCUS: Finely Orchestrated Cohesive Unwavering Service</p>
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
