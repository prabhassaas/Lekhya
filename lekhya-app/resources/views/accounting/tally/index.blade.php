@extends('layouts.app')
@section('title', 'Tally Import')
@section('page-title', 'Seedha Bill — Tally Import')

@section('content')
<div class="py-4 space-y-6">
    {{-- Upload --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fa fa-file-import text-yellow-600 text-lg"></i>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900">Import from Tally</h3>
                <p class="text-sm text-gray-500 mt-1">Export your Tally data as XML (Gateway of Tally → Export → Day Book / Vouchers), then upload it here. Lekhya parses ledgers, groups, and vouchers and maps them onto your chart of accounts before posting anything.</p>
                <form method="POST" action="{{ route('accounting.tally.upload') }}" enctype="multipart/form-data" class="mt-4 flex items-center gap-3">
                    @csrf
                    <label class="flex-1 flex items-center gap-3 border-2 border-dashed border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-navy-300 hover:bg-navy-50/30 transition">
                        <i class="fa fa-cloud-arrow-up text-gray-400"></i>
                        <span class="text-sm text-gray-500" id="tally-file-label">Choose a Tally XML export…</span>
                        <input type="file" name="file" accept=".xml" class="hidden" onchange="document.getElementById('tally-file-label').textContent = this.files[0]?.name || 'Choose a Tally XML export…'">
                    </label>
                    <button type="submit" class="px-5 py-3 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-xl">
                        Upload &amp; Parse
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Import history --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="p-5 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Import History</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($imports as $import)
            <div class="px-5 py-3 flex justify-between items-center hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <i class="fa fa-file-code text-gray-300"></i>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $import->filename }}</p>
                        <p class="text-xs text-gray-500">{{ $import->created_at->format('d M Y, g:i A') }}
                            @if($import->status === 'completed')
                                · {{ $import->imported_records }} imported
                                @if($import->failed_records) , {{ $import->failed_records }} failed @endif
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium
                        {{ match($import->status) {
                            'completed' => 'bg-green-100 text-green-700',
                            'failed' => 'bg-red-100 text-red-700',
                            'importing' => 'bg-blue-100 text-blue-700',
                            default => 'bg-gray-100 text-gray-600',
                        } }}">
                        {{ ucfirst($import->status) }}
                    </span>
                    @if(in_array($import->status, ['uploaded', 'previewed']))
                    <a href="{{ route('accounting.tally.preview', $import) }}" class="text-xs text-blue-600 hover:text-blue-700 font-medium">Review →</a>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-5 py-10 text-center text-gray-400">
                <i class="fa fa-file-import text-2xl mb-2 block"></i>
                <p class="text-sm">No imports yet. Upload a Tally XML export above to get started.</p>
            </div>
            @endforelse
        </div>
        @if($imports->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $imports->links() }}</div>
        @endif
    </div>
</div>
@endsection
