@extends('layouts.marketing')
@section('title', 'Register — Lekhya')
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
      <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">Register to Lekhya</h1>
      @if(session('error'))<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
      @if($errors->any())<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif
      @if('Register' === 'Login')
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
