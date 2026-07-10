@extends('layouts.app')
@section('title', $report ? 'Edit Audit Report' : 'New Audit Report')
@section('page-title', $report ? 'Edit Audit Report' : 'New Audit Report')

@section('content')
<div class="py-4 max-w-2xl">
    <form method="POST" action="{{ $report ? route('pramaan.audit-reports.update', $report) : route('pramaan.audit-reports.store') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
        @csrf
        @if($report) @method('PUT') @endif

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Form Type <span class="text-red-500">*</span></label>
                <select name="form_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @foreach($formTypes as $code => $label)
                    <option value="{{ $code }}" @selected(old('form_type', $report->form_type ?? '') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Financial Year <span class="text-red-500">*</span></label>
                <input type="text" name="financial_year" value="{{ old('financial_year', $report->financial_year ?? '') }}" required maxlength="7" placeholder="2024-25" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client Name <span class="text-red-500">*</span></label>
                <input type="text" name="client_name" value="{{ old('client_name', $report->report_data['client_name'] ?? '') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client PAN</label>
                <input type="text" name="client_pan" value="{{ old('client_pan', $report->report_data['client_pan'] ?? '') }}" maxlength="10" placeholder="ABCDE1234F" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reviewer</label>
                <select name="reviewer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">— Unassigned —</option>
                    @foreach($team as $member)
                    <option value="{{ $member->id }}" @selected(old('reviewer_id', $report->reviewer_id ?? '') == $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Signing Partner</label>
                <select name="signer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">— Unassigned —</option>
                    @foreach($team as $member)
                    <option value="{{ $member->id }}" @selected(old('signer_id', $report->signer_id ?? '') == $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Observations / Qualifications</label>
            <textarea name="observations" rows="4" placeholder="Audit observations, qualifications, or notes…" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('observations', $report->report_data['observations'] ?? '') }}</textarea>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg">{{ $report ? 'Save Changes' : 'Create Draft' }}</button>
            <a href="{{ $report ? route('pramaan.audit-reports.show', $report) : route('pramaan.audit-reports.index') }}" class="text-gray-500 text-sm hover:text-gray-700">Cancel</a>
        </div>
    </form>
</div>
@endsection
