@extends('layouts.app')
@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
<div class="py-4 space-y-5 max-w-3xl">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Activity across your workspace.</p>
        <div class="flex items-center gap-2">
            @if(auth()->user()->unreadNotifications()->count() > 0)
            <form method="POST" action="{{ route('notifications.read_all') }}">
                @csrf
                <button class="px-3 py-1.5 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"><i class="fa fa-check-double mr-1.5"></i>Mark all read</button>
            </form>
            @endif
            @if($notifications->isNotEmpty())
            <form method="POST" action="{{ route('notifications.clear') }}" onsubmit="return confirm('Clear all notifications?')">
                @csrf
                <button class="px-3 py-1.5 text-sm border border-red-200 text-red-600 rounded-lg hover:bg-red-50"><i class="fa fa-trash mr-1.5"></i>Clear</button>
            </form>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
        @forelse($notifications as $n)
        <a href="{{ route('notifications.open', $n->id) }}" class="flex gap-3 px-5 py-4 hover:bg-gray-50 {{ $n->read_at ? '' : 'bg-blue-50/40' }}">
            <div class="w-9 h-9 rounded-lg bg-{{ $n->data['color'] ?? 'navy' }}-50 text-{{ $n->data['color'] ?? 'navy' }}-600 flex items-center justify-center shrink-0">
                <i class="fa {{ $n->data['icon'] ?? 'fa-bell' }}"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm text-gray-900 {{ $n->read_at ? '' : 'font-semibold' }}">{{ $n->data['title'] ?? 'Notification' }}</p>
                @if(!empty($n->data['body']))<p class="text-sm text-gray-500 mt-0.5">{{ $n->data['body'] }}</p>@endif
                <p class="text-xs text-gray-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
            </div>
            @unless($n->read_at)<span class="w-2 h-2 rounded-full bg-blue-500 shrink-0 mt-2"></span>@endunless
        </a>
        @empty
        <div class="px-5 py-16 text-center text-gray-400">
            <i class="fa fa-bell-slash text-3xl text-gray-300 mb-3 block"></i>
            No notifications yet. You'll see payments, invoices and team activity here.
        </div>
        @endforelse
    </div>

    @if($notifications->hasPages())<div>{{ $notifications->links() }}</div>@endif
</div>
@endsection
