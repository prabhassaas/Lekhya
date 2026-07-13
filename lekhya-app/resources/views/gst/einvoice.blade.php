@extends('layouts.app')
@section('title', 'e-Invoice · ' . $invoice->invoice_number)
@section('page-title', 'e-Invoice (IRN)')

@section('content')
@php
    $eligible = $invoice->type === 'sales' && $invoice->status === 'posted';
@endphp
<div class="py-4 space-y-6 max-w-3xl">

    <a href="{{ route('accounting.invoices.show', $invoice) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
        <i class="fa fa-arrow-left mr-1.5"></i>Back to invoice
    </a>

    {{-- Invoice summary --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 grid grid-cols-2 gap-6">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Invoice</p>
            <p class="font-semibold text-gray-900">{{ $invoice->invoice_number }}</p>
            <p class="text-sm text-gray-500">{{ $invoice->invoice_date?->format('d M Y') }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Customer</p>
            <p class="font-semibold text-gray-900">{{ $invoice->party?->name ?? '—' }}</p>
            @if($invoice->party?->gstin)<p class="text-sm text-gray-500 font-mono">{{ $invoice->party->gstin }}</p>@endif
            <p class="text-sm text-gray-700 mt-1">₹{{ number_format($invoice->total_amount, 2) }}</p>
        </div>
    </div>

    @if($invoice->irn)
        {{-- IRN generated --}}
        <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 bg-green-50 border-b border-green-100 flex items-center gap-2">
                <i class="fa fa-circle-check text-green-600"></i>
                <h3 class="font-semibold text-green-800 text-sm">e-Invoice registered with IRP</h3>
            </div>
            <div class="p-6 grid sm:grid-cols-3 gap-6">
                <div class="sm:col-span-2 space-y-4">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">IRN (Invoice Reference Number)</p>
                        <p class="font-mono text-sm text-gray-900 break-all">{{ $invoice->irn }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Ack No.</p>
                            <p class="font-mono text-sm text-gray-700">{{ $invoice->ack_number ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Ack Date</p>
                            <p class="text-sm text-gray-700">{{ $invoice->ack_date?->format('d M Y, H:i') ?: '—' }}</p>
                        </div>
                    </div>
                    @if($invoice->eway_bill_number)
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">e-Way Bill</p>
                        <p class="font-mono text-sm text-gray-700">{{ $invoice->eway_bill_number }}</p>
                    </div>
                    @endif
                </div>
                <div class="flex flex-col items-center justify-center border-l border-gray-100 pl-6">
                    <div id="qrbox" class="w-32 h-32 bg-white border border-gray-200 rounded-lg flex items-center justify-center p-2"></div>
                    <p class="text-[10px] text-gray-400 mt-2 text-center">Signed QR</p>
                </div>
            </div>
            @if($invoice->signed_qr)
            <details class="px-6 pb-5">
                <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600">Show signed QR payload</summary>
                <pre class="mt-2 text-[10px] text-gray-500 bg-gray-50 rounded-lg p-3 overflow-x-auto break-all whitespace-pre-wrap">{{ $invoice->signed_qr }}</pre>
            </details>
            @endif
        </div>

        {{-- Minimal dependency-free QR from the signed payload --}}
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
        <script>
            (function () {
                var payload = @json($invoice->signed_qr ?? $invoice->irn);
                try {
                    var qr = qrcode(0, 'M');
                    qr.addData(payload);
                    qr.make();
                    document.getElementById('qrbox').innerHTML = qr.createSvgTag({ scalable: true });
                    var svg = document.querySelector('#qrbox svg');
                    if (svg) { svg.style.width = '100%'; svg.style.height = '100%'; }
                } catch (e) {
                    document.getElementById('qrbox').innerHTML = '<i class="fa fa-qrcode text-4xl text-gray-300"></i>';
                }
            })();
        </script>
        @endpush

    @elseif($eligible)
        {{-- Eligible, not yet generated --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 text-center">
            <div class="w-14 h-14 mx-auto rounded-full bg-navy-50 flex items-center justify-center mb-4">
                <i class="fa fa-qrcode text-navy-600 text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900">Generate IRN for this invoice</h3>
            <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">Register this invoice on the Invoice Registration Portal (IRP) to obtain the IRN, acknowledgement and signed QR code required for a valid e-invoice.</p>
            <form method="POST" action="{{ route('gst.einvoice.generate', $invoice) }}" class="mt-5">
                @csrf
                <button class="px-5 py-2.5 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
                    <i class="fa fa-bolt mr-1.5"></i>Generate IRN
                </button>
            </form>
            <p class="text-xs text-gray-400 mt-4">Routed through the configured GSP gateway. In demo mode a mock IRN is issued.</p>
        </div>
    @else
        {{-- Not eligible --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 text-center">
            <div class="w-14 h-14 mx-auto rounded-full bg-amber-50 flex items-center justify-center mb-4">
                <i class="fa fa-triangle-exclamation text-amber-500 text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900">e-Invoice not available</h3>
            <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                IRN can only be generated for <strong>posted sales invoices</strong>.
                @if($invoice->type !== 'sales') This is a {{ $invoice->type }} document. @endif
                @if($invoice->status !== 'posted') Post the invoice to the ledger first. @endif
            </p>
        </div>
    @endif
</div>
@endsection
