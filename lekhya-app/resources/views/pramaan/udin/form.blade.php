@extends('layouts.app')
@section('title', $udin ? 'Edit UDIN' : 'Generate UDIN')
@section('page-title', $udin ? 'Edit UDIN' : 'Generate UDIN')

@section('content')
<div class="py-4 max-w-2xl">
    <form method="POST" action="{{ $udin ? route('pramaan.udin.update', $udin) : route('pramaan.udin.store') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
        @csrf
        @if($udin) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">UDIN <span class="text-red-500">*</span></label>
            @if($udin)
            <input type="text" value="{{ $udin->udin }}" readonly class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm font-mono text-gray-500">
            <p class="text-xs text-gray-400 mt-1">The UDIN itself cannot be changed once registered.</p>
            @else
            <input type="text" name="udin" value="{{ old('udin') }}" required maxlength="25" placeholder="e.g. 24123456AAAABC1234" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
            <p class="text-xs text-gray-400 mt-1">The 18-digit UDIN generated on the ICAI portal.</p>
            @endif
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ICAI Membership No. <span class="text-red-500">*</span></label>
                <input type="text" name="membership_number" value="{{ old('membership_number', $udin->membership_number ?? '') }}" required maxlength="15" placeholder="e.g. 234567" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Document Date <span class="text-red-500">*</span></label>
                <input type="date" name="document_date" value="{{ old('document_date', optional($udin->document_date ?? null)->format('Y-m-d')) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Document Type <span class="text-red-500">*</span></label>
            <select name="document_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                @foreach(['Tax Audit Report (3CD)', 'Statutory Audit Report', 'Balance Sheet Certification', 'Net Worth Certificate', 'Turnover Certificate', 'Form 15CB', 'GST Audit', 'Other Certificate'] as $dt)
                <option value="{{ $dt }}" @selected(old('document_type', $udin->document_type ?? '') === $dt)>{{ $dt }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client Name <span class="text-red-500">*</span></label>
                <input type="text" name="client_name" value="{{ old('client_name', $udin->client_name ?? '') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client PAN</label>
                <input type="text" name="client_pan" value="{{ old('client_pan', $udin->client_pan ?? '') }}" maxlength="10" placeholder="ABCDE1234F" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Particulars / Figures</label>
            <textarea name="particulars" rows="3" placeholder="Key financial figures certified (turnover, net profit, etc.)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('particulars', $udin->particulars ?? '') }}</textarea>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg">{{ $udin ? 'Save Changes' : 'Register UDIN' }}</button>
            <a href="{{ route('pramaan.udin.index') }}" class="text-gray-500 text-sm hover:text-gray-700">Cancel</a>
        </div>
    </form>
</div>
@endsection
