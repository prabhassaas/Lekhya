@extends('layouts.admin')

@section('title', 'Super Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- ── Stat Cards ─────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Tenants</span>
            <span class="w-8 h-8 rounded-lg bg-blue-900 bg-opacity-60 flex items-center justify-center">
                <i class="fa fa-building text-blue-400 text-sm"></i>
            </span>
        </div>
        <p class="text-3xl font-bold text-white">{{ number_format($stats['total_tenants']) }}</p>
        <p class="text-xs text-gray-500 mt-1">All registered workspaces</p>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Active Subscriptions</span>
            <span class="w-8 h-8 rounded-lg bg-green-900 bg-opacity-60 flex items-center justify-center">
                <i class="fa fa-circle-check text-green-400 text-sm"></i>
            </span>
        </div>
        <p class="text-3xl font-bold text-white">{{ number_format($stats['active_subscriptions']) }}</p>
        <p class="text-xs text-gray-500 mt-1">Paid, non-trial</p>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">On Trial</span>
            <span class="w-8 h-8 rounded-lg bg-amber-900 bg-opacity-60 flex items-center justify-center">
                <i class="fa fa-hourglass-half text-amber-400 text-sm"></i>
            </span>
        </div>
        <p class="text-3xl font-bold text-white">{{ number_format($stats['trial_tenants']) }}</p>
        <p class="text-xs text-gray-500 mt-1">Active trials</p>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">MRR Estimate</span>
            <span class="w-8 h-8 rounded-lg bg-violet-900 bg-opacity-60 flex items-center justify-center">
                <i class="fa fa-indian-rupee-sign text-violet-400 text-sm"></i>
            </span>
        </div>
        <p class="text-3xl font-bold text-white">₹{{ number_format($stats['mrr_estimate'], 0) }}</p>
        <p class="text-xs text-gray-500 mt-1">Monthly recurring revenue</p>
    </div>

</div>

{{-- ── Two-column row: Recent sign-ups + Plan breakdown ───────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Recent sign-ups table --}}
    <div class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-white">Recent Sign-ups</h2>
            <a href="{{ route('admin.tenants') }}" class="text-xs text-violet-400 hover:text-violet-300">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500">Tenant</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500">Plan</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500">Users</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500">Joined</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($recentTenants as $tenant)
                    @php
                        $entitlement = $tenant->entitlements->first();
                        $isTrial = $entitlement && $entitlement->trial_ends_at && $entitlement->trial_ends_at->isFuture();
                    @endphp
                    <tr class="hover:bg-gray-800 transition">
                        <td class="px-5 py-3">
                            <div>
                                <p class="text-white font-medium leading-tight">{{ $tenant->name }}</p>
                                @if($tenant->gstin)
                                <p class="text-gray-500 text-xs mt-0.5 font-mono">{{ $tenant->gstin }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            @if($entitlement)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $isTrial ? 'bg-amber-900 bg-opacity-50 text-amber-300' : 'bg-green-900 bg-opacity-50 text-green-300' }}">
                                @if($isTrial)<i class="fa fa-hourglass-half text-xs"></i>@endif
                                {{ ucfirst($entitlement->plan ?? 'N/A') }}
                            </span>
                            @else
                            <span class="text-gray-600 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-300">{{ $tenant->users_count }}</td>
                        <td class="px-5 py-3 text-gray-400 text-xs">{{ $tenant->created_at->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.tenants.show', $tenant) }}"
                               class="text-violet-400 hover:text-violet-300 text-xs">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-gray-600">No tenants yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Plan breakdown: CSS-only bar chart --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h2 class="text-sm font-semibold text-white mb-5">Plan Breakdown</h2>
        @if($planBreakdown->isEmpty())
        <p class="text-gray-600 text-sm text-center py-8">No data yet.</p>
        @else
        @php $maxCount = $planBreakdown->max('count') ?: 1; @endphp
        <div class="space-y-4">
            @foreach($planBreakdown as $row)
            @php
                $pct    = round(($row->count / $maxCount) * 100);
                $colors = ['practice' => 'bg-blue-500', 'solo' => 'bg-violet-500', 'firm' => 'bg-green-500'];
                $bar    = $colors[$row->plan] ?? 'bg-gray-500';
            @endphp
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-sm text-gray-300 capitalize">{{ $row->plan ?? 'Unknown' }}</span>
                    <span class="text-sm font-semibold text-white">{{ $row->count }}</span>
                </div>
                <div class="h-2 bg-gray-800 rounded-full overflow-hidden">
                    <div class="{{ $bar }} h-2 rounded-full transition-all duration-500"
                         style="width: {{ $pct }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="mt-6 pt-5 border-t border-gray-800">
            <a href="{{ route('admin.feature-flags') }}"
               class="flex items-center justify-between text-sm text-gray-400 hover:text-violet-300 transition group">
                <span><i class="fa fa-flag mr-2 text-gray-600 group-hover:text-violet-400"></i>Feature Flags</span>
                <i class="fa fa-chevron-right text-xs"></i>
            </a>
            <a href="{{ route('admin.audit-log') }}"
               class="flex items-center justify-between text-sm text-gray-400 hover:text-violet-300 transition group mt-3">
                <span><i class="fa fa-scroll mr-2 text-gray-600 group-hover:text-violet-400"></i>Audit Log</span>
                <i class="fa fa-chevron-right text-xs"></i>
            </a>
        </div>
    </div>

</div>

@endsection
