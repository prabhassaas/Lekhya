@extends('layouts.admin')

@section('title', 'All Tenants')
@section('page-title', 'All Tenants')

@section('content')

{{-- ── Filter bar ─────────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('admin.tenants') }}"
      class="bg-gray-900 border border-gray-800 rounded-xl p-4 mb-6 flex flex-wrap gap-3 items-end">

    <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
        <div class="relative">
            <i class="fa fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Name, GSTIN or email…"
                   class="w-full bg-gray-800 border border-gray-700 text-white text-sm rounded-lg pl-9 pr-4 py-2 focus:outline-none focus:border-violet-500 placeholder-gray-600">
        </div>
    </div>

    <div class="w-40">
        <label class="block text-xs font-medium text-gray-500 mb-1">Plan</label>
        <select name="plan"
                class="w-full bg-gray-800 border border-gray-700 text-white text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-violet-500">
            <option value="">All Plans</option>
            <option value="solo"     {{ request('plan') === 'solo'     ? 'selected' : '' }}>Solo</option>
            <option value="practice" {{ request('plan') === 'practice' ? 'selected' : '' }}>Practice</option>
            <option value="firm"     {{ request('plan') === 'firm'     ? 'selected' : '' }}>Firm</option>
        </select>
    </div>

    <div class="w-40">
        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
        <select name="status"
                class="w-full bg-gray-800 border border-gray-700 text-white text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-violet-500">
            <option value="">All Status</option>
            <option value="trial"     {{ request('status') === 'trial'     ? 'selected' : '' }}>Trial</option>
            <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Active</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
    </div>

    <div class="flex gap-2">
        <button type="submit"
                class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg transition">
            <i class="fa fa-filter mr-1.5"></i>Filter
        </button>
        @if(request()->hasAny(['search','plan','status']))
        <a href="{{ route('admin.tenants') }}"
           class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 text-sm rounded-lg transition">
            Clear
        </a>
        @endif
    </div>

    <div class="ml-auto text-xs text-gray-500 self-center">
        {{ $tenants->total() }} {{ Str::plural('result', $tenants->total()) }}
    </div>
</form>

{{-- ── Table ───────────────────────────────────────────────────────────────── --}}
<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-800 text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <th class="text-left px-5 py-3.5">Tenant</th>
                    <th class="text-left px-5 py-3.5">GSTIN</th>
                    <th class="text-left px-5 py-3.5">Plan</th>
                    <th class="text-left px-5 py-3.5">Status</th>
                    <th class="text-left px-5 py-3.5">Users</th>
                    <th class="text-left px-5 py-3.5">Joined</th>
                    <th class="px-5 py-3.5">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse($tenants as $tenant)
                @php
                    $entitlement = $tenant->entitlements->first();
                    $isTrial     = $entitlement && $entitlement->trial_ends_at && $entitlement->trial_ends_at->isFuture();
                    $isActive    = $entitlement && $entitlement->is_active && ! $isTrial;
                    $isCancelled = ! $entitlement || ! $entitlement->is_active;
                @endphp
                <tr class="hover:bg-gray-800 transition group">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-900 bg-opacity-60 flex items-center justify-center flex-shrink-0">
                                <span class="text-violet-300 text-xs font-bold">
                                    {{ strtoupper(substr($tenant->name, 0, 2)) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-white font-medium leading-tight">{{ $tenant->name }}</p>
                                @if($tenant->email)
                                <p class="text-gray-500 text-xs mt-0.5">{{ $tenant->email }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        @if($tenant->gstin)
                        <span class="font-mono text-gray-300 text-xs">{{ $tenant->gstin }}</span>
                        @else
                        <span class="text-gray-600">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        @if($entitlement)
                        <span class="text-gray-300 capitalize text-xs">{{ $entitlement->plan ?? '—' }}</span>
                        @else
                        <span class="text-gray-600">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        @if($isTrial)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-900 bg-opacity-50 text-amber-300">
                            <i class="fa fa-hourglass-half text-xs"></i> Trial
                        </span>
                        @elseif($isActive)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-900 bg-opacity-50 text-green-300">
                            <i class="fa fa-circle text-xs"></i> Active
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-900 bg-opacity-50 text-red-300">
                            <i class="fa fa-circle-xmark text-xs"></i> Cancelled
                        </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-gray-300">{{ $tenant->users_count }}</td>
                    <td class="px-5 py-4 text-gray-400 text-xs whitespace-nowrap">
                        {{ $tenant->created_at->format('d M Y') }}
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
                            <a href="{{ route('admin.tenants.show', $tenant) }}"
                               class="text-xs px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition">
                                View
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-12 text-center text-gray-600">
                        <i class="fa fa-building text-3xl mb-3 block text-gray-700"></i>
                        No tenants found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tenants->hasPages())
    <div class="px-5 py-4 border-t border-gray-800 flex items-center justify-between">
        <p class="text-xs text-gray-500">
            Showing {{ $tenants->firstItem() }}–{{ $tenants->lastItem() }} of {{ $tenants->total() }}
        </p>
        <div class="flex items-center gap-1">
            @if($tenants->onFirstPage())
            <span class="px-3 py-1.5 text-xs text-gray-600 bg-gray-800 rounded-lg cursor-not-allowed">← Prev</span>
            @else
            <a href="{{ $tenants->previousPageUrl() }}"
               class="px-3 py-1.5 text-xs text-gray-300 bg-gray-800 hover:bg-gray-700 rounded-lg transition">← Prev</a>
            @endif

            @foreach($tenants->getUrlRange(max(1, $tenants->currentPage()-2), min($tenants->lastPage(), $tenants->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}"
               class="px-3 py-1.5 text-xs rounded-lg transition
                      {{ $page === $tenants->currentPage()
                         ? 'bg-violet-600 text-white'
                         : 'text-gray-400 bg-gray-800 hover:bg-gray-700' }}">
                {{ $page }}
            </a>
            @endforeach

            @if($tenants->hasMorePages())
            <a href="{{ $tenants->nextPageUrl() }}"
               class="px-3 py-1.5 text-xs text-gray-300 bg-gray-800 hover:bg-gray-700 rounded-lg transition">Next →</a>
            @else
            <span class="px-3 py-1.5 text-xs text-gray-600 bg-gray-800 rounded-lg cursor-not-allowed">Next →</span>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection
