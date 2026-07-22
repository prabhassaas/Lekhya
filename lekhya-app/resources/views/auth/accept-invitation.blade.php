@extends('layouts.marketing')
@section('title', 'Accept invitation — ' . config('app.name'))
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
      <h1 class="text-2xl font-bold text-gray-900 mb-1 text-center">You're invited</h1>
      <p class="text-center text-sm text-gray-500 mb-6">Join <span class="font-semibold text-gray-700">{{ $company }}</span> — set a password to activate your account.</p>

      @if($errors->any())<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

      <form method="POST" action="{{ route('invitation.accept', $token) }}" class="space-y-4">
        @csrf
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
          <input type="text" value="{{ $user->name }}" disabled class="w-full border border-gray-200 bg-gray-50 text-gray-500 rounded-lg px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input type="email" value="{{ $user->email }}" disabled class="w-full border border-gray-200 bg-gray-50 text-gray-500 rounded-lg px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Create password (min 8 chars)</label>
          <input type="password" name="password" required minlength="8" autofocus class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-navy-600">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
          <input type="password" name="password_confirmation" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-navy-600">
        </div>
        <button type="submit" class="w-full bg-navy-600 hover:bg-navy-700 text-white font-semibold py-2.5 rounded-lg transition">Activate my account</button>
      </form>
    </div>
    <p class="text-center text-xs text-gray-400 mt-4">Invited by mistake? You can ignore this page.</p>
  </div>
</div>
@endsection
