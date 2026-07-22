@extends('accounting.reports.pdf.layout')
@section('report_title', ucfirst($type) . ' Register')
@section('report_period', \Carbon\Carbon::parse($from)->format('d M Y') . ' — ' . \Carbon\Carbon::parse($to)->format('d M Y'))

@section('body')
<table class="data">
    <tr>
        <th style="width: 60px;">Date</th>
        <th>{{ $type === 'sales' ? 'Invoice #' : 'Bill #' }}</th>
        <th>{{ $type === 'sales' ? 'Customer' : 'Vendor' }}</th>
        <th class="num">Taxable</th>
        <th class="num">CGST</th>
        <th class="num">SGST</th>
        <th class="num">IGST</th>
        <th class="num">Total</th>
    </tr>
    @forelse($invoices as $inv)
    <tr>
        <td class="muted">{{ $inv->invoice_date->format('d M y') }}</td>
        <td>{{ $inv->invoice_number }}</td>
        <td>{{ \Illuminate\Support\Str::limit($inv->party->name ?? '—', 26) }}</td>
        <td class="num">₹{{ number_format((float) $inv->taxable_amount, 2) }}</td>
        <td class="num">₹{{ number_format((float) $inv->cgst_amount, 2) }}</td>
        <td class="num">₹{{ number_format((float) $inv->sgst_amount, 2) }}</td>
        <td class="num">₹{{ number_format((float) $inv->igst_amount, 2) }}</td>
        <td class="num">₹{{ number_format((float) $inv->total_amount, 2) }}</td>
    </tr>
    @empty
    <tr><td colspan="8" class="muted" style="text-align:center; padding:14px;">No {{ $type }} documents in this period.</td></tr>
    @endforelse
    <tr class="grand">
        <td colspan="3">Total</td>
        <td class="num">₹{{ number_format($totals['taxable'], 2) }}</td>
        <td class="num">₹{{ number_format($totals['cgst'], 2) }}</td>
        <td class="num">₹{{ number_format($totals['sgst'], 2) }}</td>
        <td class="num">₹{{ number_format($totals['igst'], 2) }}</td>
        <td class="num">₹{{ number_format($totals['total'], 2) }}</td>
    </tr>
</table>
@endsection
