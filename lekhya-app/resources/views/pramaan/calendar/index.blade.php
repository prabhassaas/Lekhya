@extends('layouts.app')
@section('title', 'Compliance Calendar')
@section('page-title', 'Compliance Calendar')

@section('content')
@php
    $statusBadge = [
        'pending'     => ['bg-gray-100', 'text-gray-600'],
        'in_progress' => ['bg-blue-100', 'text-blue-700'],
        'filed'       => ['bg-green-100', 'text-green-700'],
        'overdue'     => ['bg-red-100', 'text-red-700'],
    ];
@endphp
<div class="py-4 space-y-6" x-data="{ addOpen: false }">

    {{-- Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['Overdue', $stats['overdue'], 'text-red-600'],
            ['Due this week', $stats['due_week'], 'text-amber-600'],
            ['Pending', $stats['pending'], 'text-gray-900'],
            ['Filed', $stats['filed'], 'text-green-600'],
        ] as [$label, $value, $color])
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">{{ $label }}</p>
            <p class="text-2xl font-bold {{ $color }} mt-1">{{ $value }}</p>
        </div>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-end gap-2">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select name="status" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    <option value="">All</option>
                    @foreach(['pending','in_progress','filed','overdue'] as $s)
                    <option value="{{ $s }}" @selected($filterStatus === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Type</label>
                <select name="type" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    <option value="">All</option>
                    @foreach($types as $t)
                    <option value="{{ $t }}" @selected($filterType === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <button class="px-4 py-1.5 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">Filter</button>
        </form>
        <button @click="addOpen = !addOpen" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">
            <i class="fa fa-plus mr-1.5"></i>Add Task
        </button>
    </div>

    {{-- Add form --}}
    <div x-show="addOpen" x-cloak class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <form method="POST" action="{{ route('pramaan.calendar.store') }}" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Client <span class="text-red-500">*</span></label>
                <input type="text" name="client_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Type <span class="text-red-500">*</span></label>
                <select name="compliance_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @foreach($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Period <span class="text-red-500">*</span></label>
                <input type="text" name="period" required placeholder="e.g. Jun 2025 / FY 2024-25" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Due Date <span class="text-red-500">*</span></label>
                <input type="date" name="due_date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Assign To</label>
                <select name="assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">— Unassigned —</option>
                    @foreach($team as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg">Add</button>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Due Date</th>
                    <th class="text-left px-5 py-2.5">Client</th>
                    <th class="text-left px-5 py-2.5">Type</th>
                    <th class="text-left px-5 py-2.5">Period</th>
                    <th class="text-left px-5 py-2.5">Assignee</th>
                    <th class="text-right px-5 py-2.5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($items as $item)
                @php($effective = $item->isOverdue() ? 'overdue' : $item->status)
                @php($b = $statusBadge[$effective] ?? ['bg-gray-100','text-gray-600'])
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 {{ $item->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-700' }}">{{ $item->due_date->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-gray-900">{{ $item->client_name }}</td>
                    <td class="px-5 py-3"><span class="text-xs px-2 py-0.5 rounded-full bg-navy-50 text-navy-600 font-medium">{{ $item->compliance_type }}</span></td>
                    <td class="px-5 py-3 text-gray-500">{{ $item->period }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $item->assignee->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-right">
                        <form method="POST" action="{{ route('pramaan.calendar.update', $item) }}" class="inline">
                            @csrf @method('PATCH')
                            <select name="status" onchange="this.form.submit()" class="text-xs rounded-full font-medium border-0 {{ $b[0] }} {{ $b[1] }} px-2 py-1 cursor-pointer">
                                @foreach(['pending'=>'Pending','in_progress'=>'In Progress','filed'=>'Filed'] as $val => $lbl)
                                <option value="{{ $val }}" @selected($item->status === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No compliance tasks. Click “Add Task” to create one.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($items->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $items->links() }}</div>
        @endif
    </div>
</div>
@endsection
