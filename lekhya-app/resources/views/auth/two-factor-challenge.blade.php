@extends('layouts.marketing')
@section('title', 'Two-factor verification — ' . config('app.name'))
@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <a href="{{ route('marketing.home') }}" class="inline-flex items-center space-x-2">
        <div class="w-10 h-10 bg-navy-600 rounded-xl flex items-center justify-center"><img src="{{ asset('logo-mark.svg') }}" alt="{{ config('app.name') }}" class="w-6 h-6"></div>
        <span class="text-navy-600 font-bold text-2xl">{{ config('app.name') }}</span>
      </a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
      <h1 class="text-xl font-bold text-gray-900 mb-1 text-center"><i class="fa fa-shield-halved text-navy-600 mr-1.5"></i>Two-factor verification</h1>
      <p class="text-center text-sm text-gray-500 mb-6">Open your authenticator app and enter the current 6-digit code.</p>

      @if($errors->any())<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

      <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
        @csrf
        <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus
               placeholder="000000"
               class="w-full border border-gray-300 rounded-lg px-3 py-3 text-center text-2xl tracking-[0.4em] focus:outline-none focus:ring-2 focus:ring-navy-600">
        <button type="submit" class="w-full bg-navy-600 hover:bg-navy-700 text-white font-semibold py-2.5 rounded-lg transition">Verify</button>
      </form>

      <div class="mt-5 text-center">
        <p class="text-xs text-gray-500">Lost your device? Enter one of your <span class="font-medium text-gray-600">recovery codes</span> above instead.</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
          @csrf
          <button class="text-sm text-gray-400 hover:text-gray-600">← Back to sign in</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
