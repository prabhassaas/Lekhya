@extends('layouts.app')
@section('title', 'All Clients')
@section('page-title', 'All Clients')

@section('content')
<div class="py-4 space-y-6">
    <div class="grid grid-cols-3 gap-4">
        @foreach([
            ['Clients', $totals['clients'], 'fa-users', 'text-navy-600'],
            ['Audit Reports', $totals['reports'], 'fa-scroll', 'text-amber-600'],
            ['Open Notices', $totals['notices'], 'fa-triangle-exclamation', 'text-red-600'],
        ] as [$label, $value, $icon, $color])
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center"><i class="fa {{ $icon }} {{ $color }}"></i></div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">{{ $label }}</p>
                <p class="text-xl font-bold text-gray-900">{{ $value }}</p>
            </div>
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Client</th>
                    <th class="text-right px-5 py-2.5">Open Tasks</th>
                    <th class="text-right px-5 py-2.5">Overdue</th>
                    <th class="text-right px-5 py-2.5">Audit Reports</th>
                    <th class="text-right px-5 py-2.5">Open Notices</th>
                    <th class="text-left px-5 py-2.5">Next Due</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($clients as $c)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">{{ strtoupper(substr($c['name'], 0, 1)) }}</div>
                            <span class="text-gray-900 font-medium">{{ $c['name'] }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ $c['open'] }}</td>
                    <td class="px-5 py-3 text-right">
                        @if($c['overdue'] > 0)<span class="text-red-600 font-semibold">{{ $c['overdue'] }}</span>@else<span class="text-gray-300">0</span>@endif
                    </td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ $c['audit_reports'] }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ $c['notices'] }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $c['next_due'] ? $c['next_due']->format('d M Y') : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No clients yet. Clients appear here as you add compliance tasks, audit reports, or notices for them.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
