@extends('layouts.app')
@section('title', 'Review Tally Import')
@section('page-title', 'Review Import — ' . $import->filename)

@section('content')
@php $summary = $import->summary ?? []; @endphp
<div class="py-4 space-y-6">
    <a href="{{ route('accounting.tally.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
        <i class="fa fa-arrow-left mr-1.5"></i>Back to imports
    </a>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Groups</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['groups'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Ledgers</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['ledgers'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Parties</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['parties'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Vouchers</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['vouchers'] ?? 0 }}</p>
        </div>
    </div>

    @if(!empty($summary['errors']))
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <p class="font-medium text-amber-900 text-sm mb-2"><i class="fa fa-triangle-exclamation mr-1.5"></i>{{ count($summary['errors']) }} issue(s) found</p>
        <ul class="text-sm text-amber-700 space-y-1 list-disc list-inside">
            @foreach($summary['errors'] as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-1">Ready to import</h3>
        <p class="text-sm text-gray-500 mb-4">Groups and ledgers will be mapped onto your chart of accounts; vouchers will be posted as journal entries. This action is idempotent — re-running a completed import won't create duplicates.</p>
        <form method="POST" action="{{ route('accounting.tally.run', $import) }}">
            @csrf
            <button class="px-5 py-3 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-xl">
                <i class="fa fa-play mr-1.5"></i>Run Import
            </button>
        </form>
    </div>
</div>
@endsection
