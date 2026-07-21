@extends('layouts.app')
@section('title', 'Companies')
@section('page-title', 'Companies')

@section('content')
<div class="py-4 space-y-5 max-w-4xl">
    @include('settings._nav')

    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-500">
            Using <strong class="text-gray-800">{{ $used }}</strong> of
            <strong class="text-gray-800">{{ $limit >= PHP_INT_MAX ? '∞' : $limit }}</strong> companies on your plan.
        </div>
        @if($canAdd)
        <a href="{{ route('companies.create') }}" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg"><i class="fa fa-plus mr-1.5"></i>Add company</a>
        @else
        <a href="{{ route('settings.billing') }}" class="px-4 py-2 border border-navy-600 text-navy-700 text-sm font-medium rounded-lg hover:bg-navy-50"><i class="fa fa-arrow-up mr-1.5"></i>Upgrade for more</a>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
        @foreach($companies as $co)
        <div class="flex items-center justify-between px-5 py-4">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-lg bg-navy-50 text-navy-600 flex items-center justify-center font-bold shrink-0">{{ strtoupper(substr($co->name, 0, 1)) }}</div>
                <div class="min-w-0">
                    <p class="font-semibold text-gray-900 truncate">{{ $co->name }}
                        @if($co->id === $activeId)<span class="ml-2 text-[11px] px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">Active</span>@endif
                        @if(!$co->owner_tenant_id)<span class="ml-1 text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Primary</span>@endif
                    </p>
                    <p class="text-sm text-gray-500 font-mono">{{ $co->gstin ?: 'No GSTIN' }}</p>
                </div>
            </div>
            @if($co->id !== $activeId)
            <form method="POST" action="{{ route('companies.switch', $co) }}">
                @csrf
                <button class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">Switch to this</button>
            </form>
            @else
            <span class="text-sm text-gray-400">You're here</span>
            @endif
        </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-400">Each company keeps its own GSTIN, books, invoices and reports. Switching is instant — your subscription covers all your companies up to the plan limit.</p>
</div>
@endsection
