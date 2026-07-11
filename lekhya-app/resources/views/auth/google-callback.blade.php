@extends('layouts.marketing')
@section('title', 'Signing in — Lekhya')
@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
    <div class="text-center">
        <a href="{{ route('marketing.home') }}" class="inline-flex items-center space-x-2 mb-8">
            <div class="w-12 h-12 bg-navy-600 rounded-2xl flex items-center justify-center">
                <img src="{{ asset('logo-mark.svg') }}" alt="Lekhya" class="w-7 h-7">
            </div>
            <span class="text-navy-600 font-bold text-2xl">Lekhya</span>
        </a>

        <p class="text-gray-600 text-sm mb-4" id="status-text">Completing your sign-in...</p>

        <div class="w-56 h-1.5 bg-gray-200 rounded-full mx-auto overflow-hidden">
            <div id="progress-bar"
                 class="h-full bg-green-500 rounded-full"
                 style="width:0%;transition:width 0.3s ease"></div>
        </div>

        <p class="mt-6 text-xs text-gray-400" id="error-hint" style="display:none">
            <a href="{{ route('login') }}" class="text-navy-600 hover:underline">Back to sign-in</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var bar  = document.getElementById('progress-bar');
    var txt  = document.getElementById('status-text');
    var hint = document.getElementById('error-hint');

    function setProgress(pct) {
        bar.style.width = pct + '%';
    }

    function showError(msg) {
        txt.textContent  = msg;
        txt.className    = 'text-red-600 text-sm mb-4';
        hint.style.display = '';
        setProgress(0);
    }

    // Parse hash fragment — Supabase puts tokens here, not in query params
    var hash   = window.location.hash.replace(/^#/, '');
    var params = {};
    hash.split('&').forEach(function (pair) {
        var idx = pair.indexOf('=');
        if (idx > -1) {
            params[decodeURIComponent(pair.slice(0, idx))] = decodeURIComponent(pair.slice(idx + 1));
        }
    });

    var token = params['access_token'];
    if (! token) {
        showError('Sign-in failed: no token received from Google. Please try again.');
        return;
    }

    setProgress(45);

    fetch('{{ route("auth.google.verify") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN':  '{{ csrf_token() }}',
            'Accept':        'application/json',
        },
        body: JSON.stringify({ access_token: token }),
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.redirect) {
            setProgress(100);
            txt.textContent = 'Signed in! Taking you to your dashboard...';
            window.location.href = data.redirect;
        } else {
            showError(data.error || 'Something went wrong. Please try again.');
        }
    })
    .catch(function () {
        showError('Network error. Check your connection and try again.');
    });
})();
</script>
@endpush
