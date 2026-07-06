@extends('layouts.marketing')
@section('title', 'Login — Lekhya')
@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <a href="{{ route('marketing.home') }}" class="inline-flex items-center space-x-2">
        <div class="w-10 h-10 bg-navy-600 rounded-xl flex items-center justify-center"><span class="text-white font-bold">ल</span></div>
        <span class="text-navy-600 font-bold text-2xl">Lekhya</span>
      </a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">Sign in to Lekhya</h1>
      @if(session('error'))<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
      @if($errors->any())<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

      {{-- Prabhas SSO (Google) --}}
      @php $prabhasUrl = config('services.prabhas.accounts_url', 'https://accounts.prabhas.in'); @endphp
      <a href="{{ $prabhasUrl }}/login?app=lekhya&callback={{ urlencode(route('sso.handle')) }}"
         class="flex items-center justify-center gap-3 w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition mb-5">
        <svg class="w-5 h-5" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
        Continue with Google (via Prabhas)
      </a>

      <div class="relative mb-5">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
        <div class="relative flex justify-center"><span class="bg-white px-3 text-xs text-gray-400 font-medium">or sign in with email</span></div>
      </div>

      @if('Login' === 'Login')
      <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label><input type="email" name="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-navy-600" value="{{ old('email') }}"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Password</label><input type="password" name="password" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-navy-600"></div>
        <div class="flex items-center justify-between"><label class="flex items-center space-x-2 text-sm text-gray-600"><input type="checkbox" name="remember" class="rounded"><span>Remember me</span></label></div>
        <button type="submit" class="w-full bg-navy-600 hover:bg-navy-700 text-white font-semibold py-2.5 rounded-lg transition">Sign In</button>
        <p class="text-center text-sm text-gray-600">Don't have an account? <a href="{{ route('register') }}" class="text-navy-600 font-medium">Start free trial</a></p>
      </form>
      @else
      <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label><input type="text" name="company_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-navy-600" value="{{ old('company_name') }}"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">GSTIN (optional)</label><input type="text" name="gstin" class="w-full border border-gray-300 rounded-lg px-3 py-2" value="{{ old('gstin') }}" placeholder="29ABCDE1234F1Z5"></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label><input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2" value="{{ old('name') }}"></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label><input type="tel" name="phone" class="w-full border border-gray-300 rounded-lg px-3 py-2" value="{{ old('phone') }}"></div>
        </div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label><input type="email" name="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2" value="{{ old('email') }}"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Password (min 8 chars)</label><input type="password" name="password" required minlength="8" class="w-full border border-gray-300 rounded-lg px-3 py-2"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label><input type="password" name="password_confirmation" required class="w-full border border-gray-300 rounded-lg px-3 py-2"></div>
        <button type="submit" class="w-full bg-navy-600 hover:bg-navy-700 text-white font-semibold py-2.5 rounded-lg transition">Start 14-Day Free Trial</button>
        <p class="text-center text-sm text-gray-600">Already have an account? <a href="{{ route('login') }}" class="text-navy-600 font-medium">Sign in</a></p>
      </form>
      @endif
    </div>
  </div>
</div>
@endsection
