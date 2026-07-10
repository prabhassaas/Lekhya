@extends('layouts.app')
@section('title', 'Notice Tracker')
@section('page-title', 'Notice Tracker')

@section('content')
@php
    $statusBadge = [
        'received'    => ['bg-gray-100', 'text-gray-600'],
        'in_progress' => ['bg-blue-100', 'text-blue-700'],
        'replied'     => ['bg-green-100', 'text-green-700'],
        'closed'      => ['bg-gray-100', 'text-gray-500'],
        'appealed'    => ['bg-purple-100', 'text-purple-700'],
    ];
@endphp
<div class="py-4 space-y-6" x-data="{ addOpen: false }">

    <div class="grid grid-cols-3 gap-4">
        @foreach([
            ['Open', $stats['open'], 'text-gray-900'],
            ['Overdue', $stats['overdue'], 'text-red-600'],
            ['Replied', $stats['replied'], 'text-green-600'],
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
                    @foreach($statuses as $s)
                    <option value="{{ $s }}" @selected($filterStatus === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="px-4 py-1.5 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">Filter</button>
        </form>
        <button @click="addOpen = !addOpen" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">
            <i class="fa fa-plus mr-1.5"></i>Log Notice
        </button>
    </div>

    <div x-show="addOpen" x-cloak class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <form method="POST" action="{{ route('pramaan.notices.store') }}" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Client <span class="text-red-500">*</span></label>
                <input type="text" name="client_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Notice Type <span class="text-red-500">*</span></label>
                <select name="notice_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @foreach(['GST Notice (ASMT-10)','GST SCN','IT Notice u/s 143(1)','IT Notice u/s 143(2)','IT Notice u/s 148','TDS Default','ROC Notice','Other'] as $nt)
                    <option value="{{ $nt }}">{{ $nt }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Authority</label>
                <input type="text" name="authority" placeholder="GST Dept / IT Dept / ROC" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Notice Number</label>
                <input type="text" name="notice_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Notice Date <span class="text-red-500">*</span></label>
                <input type="date" name="notice_date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Response Due</label>
                <input type="date" name="response_due_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Subject</label>
                <input type="text" name="subject" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Assign To</label>
                <select name="assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">— Unassigned —</option>
                    @foreach($team as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg">Log Notice</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Client</th>
                    <th class="text-left px-5 py-2.5">Type</th>
                    <th class="text-left px-5 py-2.5">Authority</th>
                    <th class="text-left px-5 py-2.5">Notice Date</th>
                    <th class="text-left px-5 py-2.5">Response Due</th>
                    <th class="text-left px-5 py-2.5">Assignee</th>
                    <th class="text-right px-5 py-2.5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($notices as $n)
                @php($b = $statusBadge[$n->status] ?? ['bg-gray-100','text-gray-600'])
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-900 font-medium">{{ $n->client_name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $n->notice_type }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $n->authority ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $n->notice_date->format('d M Y') }}</td>
                    <td class="px-5 py-3 {{ $n->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-500' }}">{{ $n->response_due_date ? $n->response_due_date->format('d M Y') : '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $n->assignee->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-right">
                        <form method="POST" action="{{ route('pramaan.notices.update', $n) }}" class="inline">
                            @csrf @method('PATCH')
                            <select name="status" onchange="this.form.submit()" class="text-xs rounded-full font-medium border-0 {{ $b[0] }} {{ $b[1] }} px-2 py-1 cursor-pointer">
                                @foreach($statuses as $s)
                                <option value="{{ $s }}" @selected($n->status === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">No notices logged. Track GST, income-tax, and ROC notices here.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($notices->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $notices->links() }}</div>
        @endif
    </div>
</div>
@endsection
