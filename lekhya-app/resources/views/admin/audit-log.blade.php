@extends('layouts.admin')

@section('title', 'Audit Log')
@section('page-title', 'Audit Log')

@section('content')

{{-- ── Filter bar ─────────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('admin.audit-log') }}"
      class="bg-gray-900 border border-gray-800 rounded-xl p-4 mb-6 flex flex-wrap gap-3 items-end">

    <div class="w-52">
        <label class="block text-xs font-medium text-gray-500 mb-1">Tenant</label>
        <select name="tenant_id"
                class="w-full bg-gray-800 border border-gray-700 text-white text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-violet-500">
            <option value="">All Tenants</option>
            @foreach($tenants as $t)
            <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>
                {{ $t->name }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="flex-1 min-w-[160px]">
        <label class="block text-xs font-medium text-gray-500 mb-1">Event Type</label>
        <input type="text" name="event_type" value="{{ request('event_type') }}"
               placeholder="e.g. invoice.posted"
               class="w-full bg-gray-800 border border-gray-700 text-white text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-violet-500 placeholder-gray-600">
    </div>

    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="bg-gray-800 border border-gray-700 text-white text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-violet-500">
    </div>

    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="bg-gray-800 border border-gray-700 text-white text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-violet-500">
    </div>

    <div class="flex gap-2">
        <button type="submit"
                class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg transition">
            <i class="fa fa-filter mr-1.5"></i>Filter
        </button>
        @if(request()->hasAny(['tenant_id','event_type','date_from','date_to']))
        <a href="{{ route('admin.audit-log') }}"
           class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 text-sm rounded-lg transition">Clear</a>
        @endif
    </div>

    <div class="ml-auto text-xs text-gray-500 self-center">
        {{ $logs->total() }} {{ Str::plural('event', $logs->total()) }}
    </div>
</form>

{{-- ── Log table ───────────────────────────────────────────────────────────── --}}
<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-800 text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <th class="text-left px-5 py-3.5">Event</th>
                    <th class="text-left px-5 py-3.5">Tenant</th>
                    <th class="text-left px-5 py-3.5">Actor</th>
                    <th class="text-left px-5 py-3.5">Target</th>
                    <th class="text-left px-5 py-3.5">IP</th>
                    <th class="text-left px-5 py-3.5">When</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-800 transition">
                    <td class="px-5 py-3">
                        <span class="font-mono text-xs text-violet-300 bg-violet-900 bg-opacity-30 px-2 py-0.5 rounded">
                            {{ $log->event_type }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-300 text-xs">{{ $log->tenant_name ?? '—' }}</td>
                    <td class="px-5 py-3 text-xs">
                        @if($log->actor_name)
                        <p class="text-white">{{ $log->actor_name }}</p>
                        <p class="text-gray-500">{{ $log->actor_email }}</p>
                        @else
                        <span class="text-gray-600">System</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500 text-xs">
                        @if($log->auditable_type)
                        {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                        @else
                        —
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500 text-xs font-mono">{{ $log->ip_address ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500 text-xs whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-gray-600">
                        <i class="fa fa-scroll text-3xl mb-3 block text-gray-700"></i>
                        No audit events found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="px-5 py-4 border-t border-gray-800 flex items-center justify-between">
        <p class="text-xs text-gray-500">
            Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}
        </p>
        <div class="flex items-center gap-1">
            @if($logs->onFirstPage())
            <span class="px-3 py-1.5 text-xs text-gray-600 bg-gray-800 rounded-lg cursor-not-allowed">← Prev</span>
            @else
            <a href="{{ $logs->previousPageUrl() }}"
               class="px-3 py-1.5 text-xs text-gray-300 bg-gray-800 hover:bg-gray-700 rounded-lg transition">← Prev</a>
            @endif
            @if($logs->hasMorePages())
            <a href="{{ $logs->nextPageUrl() }}"
               class="px-3 py-1.5 text-xs text-gray-300 bg-gray-800 hover:bg-gray-700 rounded-lg transition">Next →</a>
            @else
            <span class="px-3 py-1.5 text-xs text-gray-600 bg-gray-800 rounded-lg cursor-not-allowed">Next →</span>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection
