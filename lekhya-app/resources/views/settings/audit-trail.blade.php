@extends('layouts.app')
@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail')

@section('content')
<div class="py-4 space-y-5 max-w-6xl">
    @include('settings._nav')

    <div class="bg-gradient-to-br from-navy-50 to-blue-50 rounded-xl border border-navy-100 p-5">
        <div class="flex items-start gap-3">
            <i class="fa fa-clipboard-list text-navy-600 text-lg mt-0.5"></i>
            <div>
                <h2 class="font-semibold text-navy-800">Statutory edit-log</h2>
                <p class="text-sm text-navy-700 mt-1 max-w-3xl">
                    Every create, change and deletion of your books of account is recorded here with who did it, when, and the exact
                    before → after values. The trail is append-only and cannot be edited or switched off — as required by the
                    Companies (Accounts) Rules, 2014 (Rule 3).
                </p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Record type</label>
            <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All records</option>
                @foreach($types as $t)
                <option value="{{ $t }}" @selected(request('type') === $t)>{{ ucwords(str_replace('_', ' ', $t)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Action</label>
            <select name="action" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All actions</option>
                @foreach(['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted'] as $k => $v)
                <option value="{{ $k }}" @selected(request('action') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <button class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">Filter</button>
        @if(request('type') || request('action'))
        <a href="{{ route('settings.audit_trail') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">When</th>
                    <th class="text-left px-5 py-2.5">User</th>
                    <th class="text-left px-5 py-2.5">Record</th>
                    <th class="text-left px-5 py-2.5">Action</th>
                    <th class="text-left px-5 py-2.5">Change (before → after)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($logs as $log)
                @php
                    [$rec, $act] = array_pad(explode('.', $log->event_type, 2), 2, '');
                    $actColor = ['created' => 'green', 'updated' => 'blue', 'deleted' => 'red'][$act] ?? 'gray';
                    $keys = array_unique(array_merge(array_keys((array) $log->before), array_keys((array) $log->after)));
                @endphp
                <tr class="hover:bg-gray-50 align-top">
                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                    <td class="px-5 py-3 text-gray-700 whitespace-nowrap">{{ $log->user->name ?? 'System' }}</td>
                    <td class="px-5 py-3 text-gray-700 whitespace-nowrap">{{ ucwords(str_replace('_', ' ', $rec)) }} <span class="text-gray-400">#{{ $log->auditable_id }}</span></td>
                    <td class="px-5 py-3"><span class="text-xs px-2 py-0.5 rounded-full font-medium bg-{{ $actColor }}-100 text-{{ $actColor }}-700 capitalize">{{ $act }}</span></td>
                    <td class="px-5 py-3 text-xs text-gray-600">
                        @if($act === 'created')
                            <span class="text-gray-400">New record created.</span>
                        @elseif($act === 'deleted')
                            <span class="text-red-500">Record deleted.</span>
                        @else
                            @foreach(array_slice($keys, 0, 6) as $k)
                            <div class="mb-0.5">
                                <span class="text-gray-400">{{ ucwords(str_replace('_', ' ', $k)) }}:</span>
                                <span class="text-red-500 line-through">{{ \Illuminate\Support\Str::limit((string)(data_get($log->before, $k) ?? '—'), 40) }}</span>
                                <i class="fa fa-arrow-right text-[9px] text-gray-300 mx-0.5"></i>
                                <span class="text-green-600">{{ \Illuminate\Support\Str::limit((string)(data_get($log->after, $k) ?? '—'), 40) }}</span>
                            </div>
                            @endforeach
                            @if(count($keys) > 6)<span class="text-gray-400">+{{ count($keys) - 6 }} more</span>@endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No audit-trail entries yet. Changes to your books will appear here.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($logs->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
