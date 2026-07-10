@extends('layouts.app')
@section('title', 'Audit Report')
@section('page-title', str_replace('_', ' ', $report->form_type) . ' — ' . ($report->report_data['client_name'] ?? ''))

@section('content')
@php
    $steps = ['draft' => 'Draft', 'under_review' => 'Under Review', 'signed' => 'Signed', 'filed' => 'Filed'];
    $order = array_keys($steps);
    $currentIdx = array_search($report->status, $order);
@endphp
<div class="py-4 max-w-3xl space-y-6">

    {{-- Workflow stepper --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between">
            @foreach($steps as $key => $label)
            @php($idx = array_search($key, $order))
            <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $idx < $currentIdx ? 'bg-green-500 text-white' : ($idx === $currentIdx ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-400') }}">
                        @if($idx < $currentIdx)<i class="fa fa-check"></i>@else{{ $idx + 1 }}@endif
                    </div>
                    <span class="text-xs mt-1.5 {{ $idx <= $currentIdx ? 'text-gray-900 font-medium' : 'text-gray-400' }}">{{ $label }}</span>
                </div>
                @if(!$loop->last)
                <div class="flex-1 h-0.5 mx-2 {{ $idx < $currentIdx ? 'bg-green-500' : 'bg-gray-100' }}"></div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap items-center gap-2">
        @if($report->status === 'draft')
            <form method="POST" action="{{ route('pramaan.audit-reports.transition', $report) }}">
                @csrf <input type="hidden" name="to" value="under_review">
                <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg"><i class="fa fa-paper-plane mr-1.5"></i>Send for Review</button>
            </form>
            <a href="{{ route('pramaan.audit-reports.edit', $report) }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"><i class="fa fa-pen mr-1.5"></i>Edit</a>
        @elseif($report->status === 'under_review')
            {{-- Sign modal --}}
            <div x-data="{ open: false }">
                <button @click="open = true" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg"><i class="fa fa-signature mr-1.5"></i>Sign &amp; Certify</button>
                <div x-show="open" x-cloak class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 p-4" @click.self="open = false">
                    <div class="bg-white rounded-xl p-6 w-full max-w-md">
                        <h3 class="font-semibold text-gray-900 mb-1">Sign this audit report</h3>
                        <p class="text-sm text-gray-500 mb-4">Attach the UDIN and DSC used to certify. Once signed, the report is locked.</p>
                        <form method="POST" action="{{ route('pramaan.audit-reports.transition', $report) }}" class="space-y-3">
                            @csrf <input type="hidden" name="to" value="signed">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">UDIN <span class="text-red-500">*</span></label>
                                <select name="udin_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">Select UDIN…</option>
                                    @forelse($udins as $u)
                                    <option value="{{ $u->id }}">{{ $u->udin }} — {{ $u->document_type }}</option>
                                    @empty
                                    <option value="" disabled>No generated UDINs — create one first</option>
                                    @endforelse
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">DSC Certificate <span class="text-red-500">*</span></label>
                                <select name="dsc_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">Select DSC…</option>
                                    @forelse($dscs as $d)
                                    <option value="{{ $d->id }}">{{ $d->holder_name }} ({{ $d->cn }})</option>
                                    @empty
                                    <option value="" disabled>No active DSC — add one to the vault first</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="flex gap-2 pt-1">
                                <button type="submit" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg">Confirm &amp; Sign</button>
                                <button type="button" @click="open = false" class="px-4 py-2 text-gray-600 text-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('pramaan.audit-reports.transition', $report) }}">
                @csrf <input type="hidden" name="to" value="draft">
                <button class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"><i class="fa fa-rotate-left mr-1.5"></i>Return to Draft</button>
            </form>
        @elseif($report->status === 'signed')
            <form method="POST" action="{{ route('pramaan.audit-reports.transition', $report) }}">
                @csrf <input type="hidden" name="to" value="filed">
                <button class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg"><i class="fa fa-file-arrow-up mr-1.5"></i>Mark as Filed</button>
            </form>
        @endif
    </div>

    {{-- Details --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm divide-y divide-gray-50">
        @foreach([
            ['Form Type', $formTypes[$report->form_type] ?? $report->form_type],
            ['Financial Year', $report->financial_year],
            ['Client', $report->report_data['client_name'] ?? '—'],
            ['Client PAN', $report->report_data['client_pan'] ?? '—'],
            ['Preparer', $report->preparer->name ?? '—'],
            ['Reviewer', $report->reviewer->name ?? 'Unassigned'],
            ['Signing Partner', $report->signer->name ?? 'Unassigned'],
            ['UDIN', $report->udin->udin ?? '—'],
            ['DSC', $report->dsc ? $report->dsc->holder_name . ' (' . $report->dsc->cn . ')' : '—'],
            ['Signed At', $report->signed_at ? $report->signed_at->format('d M Y, H:i') : '—'],
        ] as [$label, $value])
        <div class="flex justify-between px-6 py-3 text-sm">
            <span class="text-gray-500">{{ $label }}</span>
            <span class="text-gray-900 font-medium text-right">{{ $value }}</span>
        </div>
        @endforeach
    </div>

    @if($report->report_data['observations'] ?? null)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Observations / Qualifications</p>
        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $report->report_data['observations'] }}</p>
    </div>
    @endif

    {{-- Working papers --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-900">Working Papers</h3>
            <a href="{{ route('pramaan.papers.index', ['audit_report_id' => $report->id]) }}" class="text-sm text-amber-700 hover:underline">Manage <i class="fa fa-arrow-right ml-1 text-xs"></i></a>
        </div>
        @forelse($report->workingPapers as $wp)
        <div class="flex items-center justify-between py-2 text-sm border-b border-gray-50 last:border-0">
            <span class="text-gray-700"><i class="fa fa-file text-gray-400 mr-2"></i>{{ $wp->title }}</span>
            <span class="text-xs text-gray-400">{{ $wp->category ?? 'Uncategorised' }}</span>
        </div>
        @empty
        <p class="text-sm text-gray-400">No working papers attached yet.</p>
        @endforelse
    </div>

    @if($report->status === 'draft')
    <form method="POST" action="{{ route('pramaan.audit-reports.destroy', $report) }}" onsubmit="return confirm('Delete this draft report?')">
        @csrf @method('DELETE')
        <button class="text-sm text-red-600 hover:underline"><i class="fa fa-trash mr-1.5"></i>Delete draft</button>
    </form>
    @endif
</div>
@endsection
