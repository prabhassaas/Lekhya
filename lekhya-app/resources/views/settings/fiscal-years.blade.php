@extends('layouts.app')
@section('title', 'Fiscal Years')
@section('page-title', 'Settings')

@section('content')
<div class="py-4 max-w-3xl" x-data="{ addOpen: false }">
    @include('settings._nav')

    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">Accounting periods for this company. The current year is used for new vouchers and reports.</p>
        <button @click="addOpen = !addOpen" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
            <i class="fa fa-plus mr-1.5"></i>Add Year
        </button>
    </div>

    <div x-show="addOpen" x-cloak class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-4">
        <form method="POST" action="{{ route('settings.fiscal_years.store') }}" class="grid sm:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required placeholder="e.g. 2025-26" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm text-gray-700 pb-2">
                    <input type="checkbox" name="is_current" value="1" class="rounded border-gray-300"> Set as current
                </label>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Start Date <span class="text-red-500">*</span></label>
                <input type="date" name="start_date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">End Date <span class="text-red-500">*</span></label>
                <input type="date" name="end_date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="px-5 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">Add Fiscal Year</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Name</th>
                    <th class="text-left px-5 py-2.5">Period</th>
                    <th class="text-left px-5 py-2.5">Status</th>
                    <th class="text-right px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($fiscalYears as $fy)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-900">{{ $fy->name }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $fy->start_date->format('d M Y') }} — {{ $fy->end_date->format('d M Y') }}</td>
                    <td class="px-5 py-3">
                        @if($fy->is_current)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700">Current</span>
                        @elseif($fy->is_closed)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-500">Closed</span>
                        @else
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-600">Open</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        @unless($fy->is_current)
                        <form method="POST" action="{{ route('settings.fiscal_years.current', $fy) }}" class="inline">
                            @csrf @method('PATCH')
                            <button class="text-xs text-navy-600 hover:underline">Set current</button>
                        </form>
                        @endunless
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400">No fiscal years yet. Add one to start posting.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
