@extends('layouts.app')
@section('title', 'Security')
@section('page-title', 'Settings')

@section('content')
<div class="py-4 max-w-3xl">
    @include('settings._nav')

    @if(session('success'))<div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
    @if(session('info'))<div class="mb-4 p-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg text-sm">{{ session('info') }}</div>@endif
    @if($errors->any())<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fa fa-shield-halved text-navy-600"></i> Two-factor authentication
                    @if($enabled)<span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">On</span>
                    @elseif($enrolling)<span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium">Setup in progress</span>
                    @else<span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium">Off</span>@endif
                </h2>
                <p class="text-sm text-gray-500 mt-1 max-w-xl">Add a second step at login using an authenticator app (Google Authenticator, Authy, Microsoft Authenticator). Even if your password leaks, your account stays protected.</p>
            </div>
        </div>

        {{-- OFF → offer to enable --}}
        @if(! $enabled && ! $enrolling)
        <form method="POST" action="{{ route('settings.security.2fa.enable') }}" class="mt-5">
            @csrf
            <button class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg"><i class="fa fa-plus mr-1.5"></i>Enable two-factor</button>
        </form>
        @endif

        {{-- ENROLLING → scan + confirm --}}
        @if($enrolling)
        <div class="mt-6 border-t border-gray-100 pt-6 grid md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-700 mb-2">1 · Scan this QR code</p>
                <div id="totp-qr" class="inline-block p-3 bg-white border border-gray-200 rounded-lg"></div>
                <p class="text-xs text-gray-500 mt-3">Can't scan? Enter this key manually:</p>
                <code class="block mt-1 text-sm font-mono bg-gray-50 border border-gray-200 rounded px-2 py-1 break-all">{{ $secret }}</code>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-700 mb-2">2 · Enter the 6-digit code</p>
                <form method="POST" action="{{ route('settings.security.2fa.confirm') }}" class="space-y-3">
                    @csrf
                    <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000"
                           class="w-40 border border-gray-300 rounded-lg px-3 py-2 tracking-[0.3em] text-center text-lg focus:ring-2 focus:ring-navy-600" autofocus>
                    <div>
                        <button class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">Verify &amp; turn on</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('settings.security.2fa.disable') }}" class="mt-2"
                      onsubmit="var p=prompt('Confirm your password to cancel setup:'); if(!p){return false;} this.password.value=p; return true;">
                    @csrf @method('DELETE')
                    <input type="hidden" name="password">
                    <button type="submit" class="px-3 py-1.5 text-gray-500 text-sm rounded-lg hover:bg-gray-50">Cancel setup</button>
                </form>
            </div>
        </div>
        @endif

        {{-- Recovery codes (during setup or after regenerate) --}}
        @if($recoveryCodes)
        <div class="mt-6 border-t border-gray-100 pt-6">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa fa-key text-amber-500"></i>
                <p class="text-sm font-medium text-gray-700">Recovery codes</p>
            </div>
            <p class="text-xs text-gray-500 mb-3">Save these somewhere safe. Each code works once if you lose your phone. They won't be shown again.</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 max-w-lg">
                @foreach($recoveryCodes as $code)
                <code class="text-sm font-mono bg-gray-50 border border-gray-200 rounded px-2 py-1.5 text-center text-gray-800">{{ $code }}</code>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ENABLED → manage --}}
        @if($enabled)
        <div class="mt-6 border-t border-gray-100 pt-6 flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('settings.security.2fa.recovery') }}">
                @csrf
                <button class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"><i class="fa fa-arrows-rotate mr-1.5"></i>Regenerate recovery codes</button>
            </form>
            <form method="POST" action="{{ route('settings.security.2fa.disable') }}" class="flex items-center gap-2"
                  onsubmit="var p=prompt('Confirm your password to turn off two-factor:'); if(!p){return false;} this.password.value=p; return true;">
                @csrf @method('DELETE')
                <input type="hidden" name="password">
                <button type="submit" class="px-4 py-2 border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50"><i class="fa fa-shield-xmark mr-1.5"></i>Turn off two-factor</button>
            </form>
        </div>
        @endif
    </div>
</div>

@if($enrolling && $qrUri)
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    (function () {
        function render() {
            var el = document.getElementById('totp-qr');
            if (!el) return;
            if (window.QRCode) {
                new QRCode(el, { text: @json($qrUri), width: 176, height: 176, correctLevel: QRCode.CorrectLevel.M });
            } else {
                el.innerHTML = '<p class="text-xs text-gray-400 p-4">QR unavailable — use the manual key.</p>';
            }
        }
        if (document.readyState !== 'loading') render();
        else document.addEventListener('DOMContentLoaded', render);
    })();
</script>
@endif
@endsection
