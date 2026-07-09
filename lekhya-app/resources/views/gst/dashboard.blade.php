@extends('layouts.app')
@section('title', 'GST Dashboard')
@section('page-title', 'GST Compliance')

@section('content')
<div class="py-4 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500">Filing period</p>
            <p class="text-lg font-semibold text-gray-900">{{ \Carbon\Carbon::createFromFormat('mY', $period)->format('F Y') }}</p>
        </div>
        <span class="text-xs px-3 py-1.5 rounded-full font-medium bg-navy-50 text-navy-700 border border-navy-100">
            GSTIN {{ auth()->user()->tenant->gstin ?? 'not set' }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('gst.gstr1', ['period' => $period]) }}" class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-green-200 transition group">
            <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-green-100">
                <i class="fa fa-file-invoice text-green-600"></i>
            </div>
            <p class="font-semibold text-gray-900">GSTR-1</p>
            <p class="text-xs text-gray-500 mt-1">Outward supplies — sales invoices, B2B/B2C</p>
        </a>
        <a href="{{ route('gst.gstr3b', ['period' => $period]) }}" class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-blue-200 transition group">
            <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-blue-100">
                <i class="fa fa-scale-balanced text-blue-600"></i>
            </div>
            <p class="font-semibold text-gray-900">GSTR-3B</p>
            <p class="text-xs text-gray-500 mt-1">Summary return — tax liability &amp; ITC</p>
        </a>
        <a href="{{ route('gst.gstr2b', ['period' => $period]) }}" class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-purple-200 transition group">
            <div class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-purple-100">
                <i class="fa fa-code-compare text-purple-600"></i>
            </div>
            <p class="font-semibold text-gray-900">GSTR-2B Reconciliation</p>
            <p class="text-xs text-gray-500 mt-1">Match purchases against GSTN-supplied data</p>
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="p-5 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Other GST Tools</h3>
        </div>
        <div class="divide-y divide-gray-50">
            <a href="{{ route('gst.validate') }}" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50">
                <span class="text-sm text-gray-700"><i class="fa fa-check-circle text-gray-400 w-5"></i> Validate a GSTIN</span>
                <i class="fa fa-chevron-right text-xs text-gray-300"></i>
            </a>
            <div class="flex items-center justify-between px-5 py-3">
                <span class="text-sm text-gray-700"><i class="fa fa-qrcode text-gray-400 w-5"></i> e-Invoice (IRN) — generate from any posted sales invoice</span>
            </div>
        </div>
    </div>
</div>
@endsection
