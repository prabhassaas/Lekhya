@extends('layouts.admin')

@section('title', $tenant->name . ' — Tenant Detail')
@section('page-title', $tenant->name)

@section('content')

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
    <a href="{{ route('admin.tenants') }}" class="hover:text-violet-400 transition">All Tenants</a>
    <i class="fa fa-chevron-right text-xs"></i>
    <span class="text-gray-300">{{ $tenant->name }}</span>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- ── Left column (profile + subscription + entitlements) ──────────────── --}}
    <div class="space-y-5">

        {{-- Tenant profile card --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center gap-4 mb-5">
                <div class="w-14 h-14 rounded-xl bg-violet-900 bg-opacity-60 flex items-center justify-center flex-shrink-0">
                    <span class="text-violet-300 text-xl font-bold">
                        {{ strtoupper(substr($tenant->name, 0, 2)) }}
                    </span>
                </div>
                <div>
                    <h2 class="text-white font-semibold text-lg leading-tight">{{ $tenant->name }}</h2>
                    <p class="text-gray-500 text-xs mt-0.5">#{{ $tenant->id }} · {{ $tenant->ulid }}</p>
                </div>
            </div>

            <dl class="space-y-3 text-sm">
                @if($tenant->gstin)
                <div class="flex items-start justify-between">
                    <dt class="text-gray-500">GSTIN</dt>
                    <dd class="text-white font-mono text-xs">{{ $tenant->gstin }}</dd>
                </div>
                @endif
                @if($tenant->pan)
                <div class="flex items-start justify-between">
                    <dt class="text-gray-500">PAN</dt>
                    <dd class="text-white font-mono text-xs">{{ $tenant->pan }}</dd>
                </div>
                @endif
                @if($tenant->email)
                <div class="flex items-start justify-between">
                    <dt class="text-gray-500">Email</dt>
                    <dd class="text-gray-300 text-xs break-all">{{ $tenant->email }}</dd>
                </div>
                @endif
                @if($tenant->phone)
                <div class="flex items-start justify-between">
                    <dt class="text-gray-500">Phone</dt>
                    <dd class="text-gray-300 text-xs">{{ $tenant->phone }}</dd>
                </div>
                @endif
                @if($tenant->city || $tenant->state)
                <div class="flex items-start justify-between">
                    <dt class="text-gray-500">Location</dt>
                    <dd class="text-gray-300 text-xs text-right">
                        {{ implode(', ', array_filter([$tenant->city, $tenant->state])) }}
                        @if($tenant->state_code) ({{ $tenant->state_code }}) @endif
                    </dd>
                </div>
                @endif
                <div class="flex items-start justify-between">
                    <dt class="text-gray-500">Registered</dt>
                    <dd class="text-gray-300 text-xs">{{ $tenant->created_at->format('d M Y, H:i') }}</dd>
                </div>
                <div class="flex items-start justify-between">
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        @if($tenant->is_active)
                        <span class="text-green-400 text-xs font-medium">Active</span>
                        @else
                        <span class="text-red-400 text-xs font-medium">Suspended</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Subscription card --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Subscription</h3>
            @if($subscription)
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Plan</dt>
                    <dd class="text-white font-medium">{{ $subscription->plan_name ?? '—' }} ({{ $subscription->plan_tier ?? '—' }})</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        @php
                            $statusColors = [
                                'active'    => 'text-green-400',
                                'trial'     => 'text-amber-400',
                                'past_due'  => 'text-red-400',
                                'cancelled' => 'text-red-400',
                                'expired'   => 'text-gray-500',
                            ];
                        @endphp
                        <span class="text-xs font-semibold capitalize {{ $statusColors[$subscription->status] ?? 'text-gray-300' }}">
                            {{ $subscription->status }}
                        </span>
                    </dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Billing</dt>
                    <dd class="text-gray-300 text-xs capitalize">{{ $subscription->billing_cycle }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Amount</dt>
                    <dd class="text-white font-medium">₹{{ number_format($subscription->amount, 2) }}</dd>
                </div>
                @if($subscription->trial_ends_at)
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Trial ends</dt>
                    <dd class="text-amber-300 text-xs">{{ \Carbon\Carbon::parse($subscription->trial_ends_at)->format('d M Y') }}</dd>
                </div>
                @endif
                @if($subscription->current_period_end)
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Period end</dt>
                    <dd class="text-gray-300 text-xs">{{ \Carbon\Carbon::parse($subscription->current_period_end)->format('d M Y') }}</dd>
                </div>
                @endif
            </dl>
            @else
            <p class="text-gray-600 text-sm text-center py-4">No subscription found.</p>
            @endif
        </div>

        {{-- Entitlements card --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Entitlements</h3>
            @forelse($tenant->entitlements as $ent)
            <div class="flex items-start justify-between py-2 {{ ! $loop->last ? 'border-b border-gray-800' : '' }}">
                <div>
                    <p class="text-white text-sm font-medium capitalize">{{ $ent->app }} · {{ $ent->edition }}</p>
                    <p class="text-gray-500 text-xs mt-0.5">Plan: {{ $ent->plan }}</p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full
                    {{ $ent->is_active ? 'bg-green-900 bg-opacity-50 text-green-300' : 'bg-red-900 bg-opacity-50 text-red-300' }}">
                    {{ $ent->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            @empty
            <p class="text-gray-600 text-sm">No entitlements.</p>
            @endforelse
        </div>

    </div>

    {{-- ── Right column (users + audit log) ───────────────────────────────── --}}
    <div class="xl:col-span-2 space-y-6">

        {{-- Users table --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-white">
                    Users
                    <span class="ml-2 text-xs font-normal text-gray-500">({{ $tenant->users_count }})</span>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-800">
                            <th class="text-left px-5 py-3 text-xs font-medium text-gray-500">Name</th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-gray-500">Email</th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-gray-500">Roles</th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-gray-500">Last Login</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse($tenant->users as $user)
                        <tr class="hover:bg-gray-800 transition group">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full bg-gray-700 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <span class="text-white font-medium">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-400 text-xs">{{ $user->email }}</td>
                            <td class="px-5 py-3">
                                @foreach($user->getRoleNames() as $role)
                                <span class="text-xs px-1.5 py-0.5 bg-gray-700 text-gray-300 rounded">{{ $role }}</span>
                                @endforeach
                            </td>
                            <td class="px-5 py-3 text-gray-500 text-xs">
                                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="opacity-0 group-hover:opacity-100 transition">
                                    <form method="POST" action="{{ route('admin.impersonate', $user) }}">
                                        @csrf
                                        <button type="submit"
                                                class="text-xs px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition"
                                                onclick="return confirm('Impersonate {{ addslashes($user->name) }}?')">
                                            <i class="fa fa-user-secret mr-1"></i>Impersonate
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-600">No users.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent audit log --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-white">Recent Audit Events</h3>
                <a href="{{ route('admin.audit-log', ['tenant_id' => $tenant->id]) }}"
                   class="text-xs text-violet-400 hover:text-violet-300">View all →</a>
            </div>
            <div class="divide-y divide-gray-800">
                @forelse($recentAuditLogs as $log)
                <div class="px-5 py-3 flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-gray-800 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fa fa-circle-dot text-xs text-gray-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-white text-sm font-medium font-mono">{{ $log->event_type }}</span>
                            @if($log->auditable_type)
                            <span class="text-gray-500 text-xs">on {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</span>
                            @endif
                        </div>
                        @if($log->user_name)
                        <p class="text-gray-500 text-xs mt-0.5">by {{ $log->user_name }} ({{ $log->user_email }})</p>
                        @endif
                    </div>
                    <span class="text-gray-600 text-xs whitespace-nowrap flex-shrink-0">
                        {{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}
                    </span>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-gray-600">
                    <i class="fa fa-scroll text-2xl mb-2 block text-gray-700"></i>
                    No audit events recorded.
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

@endsection
