@extends('layouts.app')
@section('title', 'AI Credits')
@section('page-title', 'AI Credit Usage')

@section('content')
@php
    $used = (int) $aiCredits['used'];
    $limit = $aiCredits['unlimited'] ? 0 : (int) $aiCredits['limit'];
    $remaining = (int) $aiCredits['remaining'];
    $pct = ($limit > 0) ? min(100, round($used / $limit * 100)) : 0;
    $circ = 2 * M_PI * 52; // r = 52
    $dash = $circ * $pct / 100;
    $labels = ['extraction' => 'Invoice scans', 'nl_query' => 'AI questions', 'account_coding' => 'Auto-coding'];
    $ring = $pct >= 90 ? '#dc2626' : ($pct >= 70 ? '#f59e0b' : '#2e5a94');
@endphp
<div class="py-4 space-y-6 max-w-4xl">

    <a href="{{ route('ai.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
        <i class="fa fa-arrow-left mr-1.5"></i>Back to AI Assistant
    </a>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        {{-- Donut --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 flex flex-col items-center justify-center">
            @if($aiCredits['unlimited'])
                <div class="w-32 h-32 rounded-full bg-navy-50 flex items-center justify-center">
                    <i class="fa fa-infinity text-3xl text-navy-600"></i>
                </div>
                <p class="mt-3 text-sm font-medium text-gray-700">Unlimited credits</p>
            @else
                <svg viewBox="0 0 120 120" class="w-32 h-32 -rotate-90">
                    <circle cx="60" cy="60" r="52" fill="none" stroke="#eef2f7" stroke-width="14"/>
                    <circle cx="60" cy="60" r="52" fill="none" stroke="{{ $ring }}" stroke-width="14" stroke-linecap="round"
                            stroke-dasharray="{{ $dash }} {{ $circ }}"/>
                </svg>
                <div class="-mt-24 text-center">
                    <p class="text-3xl font-bold text-gray-900">{{ $used }}</p>
                    <p class="text-xs text-gray-400">of {{ $limit }} used</p>
                </div>
                <p class="mt-14 text-sm font-medium {{ $remaining <= 0 ? 'text-red-600' : 'text-gray-700' }}">{{ $remaining }} credits left</p>
            @endif
        </div>

        {{-- Breakdown --}}
        <div class="md:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 text-sm mb-4">This month by activity</h3>
            @if($byType->isEmpty())
                <p class="text-sm text-gray-400">No AI usage yet this month.</p>
            @else
                <div class="space-y-3">
                    @foreach($byType as $type => $count)
                    @php $w = $used > 0 ? round($count / $used * 100) : 0; @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-700">{{ $labels[$type] ?? ucwords(str_replace('_', ' ', $type)) }}</span>
                            <span class="text-gray-500 font-medium">{{ $count }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-navy-500 rounded-full" style="width: {{ $w }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
            <p class="text-xs text-gray-400 mt-5"><i class="fa fa-circle-info mr-1"></i>Credits reset on the 1st of each month. Each invoice scan or AI query uses one credit.</p>
        </div>
    </div>

    {{-- History --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-900 text-sm">Consumption history</h3></div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">When</th>
                    <th class="text-left px-5 py-2.5">Activity</th>
                    <th class="text-left px-5 py-2.5">Engine</th>
                    <th class="text-right px-5 py-2.5">Credits</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($usage as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-500">{{ $row->created_at->format('d M Y, H:i') }}</td>
                    <td class="px-5 py-3 text-gray-800">{{ $labels[$row->type] ?? ucwords(str_replace('_', ' ', $row->type)) }}</td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $row->driver ?? '—' }}</td>
                    <td class="px-5 py-3 text-right font-medium text-gray-700">1</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400">No AI usage recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($usage->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $usage->links() }}</div>
        @endif
    </div>
</div>
@endsection
