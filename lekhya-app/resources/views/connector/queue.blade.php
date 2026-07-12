@extends('layouts.app')
@section('title', 'Import Queue')
@section('page-title', 'Import Queue')

@section('content')
<div class="py-4 space-y-6">

    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Invoices pushed from Seedha Bill land here for review. Approve to post, reject to skip — nothing hits the ledger on its own.</p>
        <a href="{{ route('connector.index') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fa fa-arrow-left mr-1"></i>Back to connector</a>
    </div>

    <div class="space-y-3">
        @forelse($items as $item)
        @php
            $p = $item->normalized_payload ?: $item->raw_payload ?: [];
            $errors = $item->validation_errors ?: [];
        @endphp
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 flex items-center justify-between border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <span class="text-xs px-2.5 py-1 rounded-full font-semibold
                        @switch($item->status)
                          @case('validated') bg-green-100 text-green-700 @break
                          @case('quarantined') bg-amber-100 text-amber-700 @break
                          @default bg-gray-100 text-gray-600
                        @endswitch">
                        {{ ucfirst($item->status) }}
                    </span>
                    <span class="text-sm font-medium text-gray-800">{{ $p['invoice_number'] ?? $p['number'] ?? $item->external_id }}</span>
                    <span class="text-xs text-gray-400">{{ ucfirst(str_replace('_', ' ', $item->source)) }}</span>
                </div>
                <span class="text-xs text-gray-400">{{ $item->created_at->diffForHumans() }}</span>
            </div>

            <div class="px-5 py-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><p class="text-xs text-gray-400">Party</p><p class="font-medium">{{ $p['party_name'] ?? $p['seller_name'] ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">GSTIN</p><p class="font-mono text-xs">{{ $p['party_gstin'] ?? $p['seller_gstin'] ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Date</p><p>{{ $p['invoice_date'] ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Total</p><p class="font-bold text-navy-700">₹{{ number_format((float)($p['total_amount'] ?? 0), 2) }}</p></div>
            </div>

            @if(!empty($errors))
            <div class="px-5 pb-3 text-xs text-amber-700 space-y-0.5">
                @foreach($errors as $err)
                <div><i class="fa fa-triangle-exclamation mr-1"></i>{{ is_array($err) ? implode(' — ', $err) : $err }}</div>
                @endforeach
            </div>
            @endif

            <div class="px-5 py-3 bg-gray-50 flex items-center justify-end gap-2">
                <form method="POST" action="{{ route('connector.queue.reject', $item) }}">
                    @csrf
                    <button type="submit" class="px-4 py-1.5 border border-red-300 text-red-600 rounded-lg text-xs font-medium hover:bg-red-50"><i class="fa fa-times mr-1"></i>Reject</button>
                </form>
                <form method="POST" action="{{ route('connector.queue.approve', $item) }}">
                    @csrf
                    <button type="submit" class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-medium"><i class="fa fa-check mr-1"></i>Approve</button>
                </form>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
            <i class="fa fa-inbox text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">The queue is empty. Imported invoices awaiting review will appear here.</p>
        </div>
        @endforelse
    </div>

    @if($items->hasPages())
    <div>{{ $items->links() }}</div>
    @endif
</div>
@endsection
