@extends('accounting.reports.pdf.layout')
@section('report_title', 'Accounts Payable Aging')
@section('report_period', 'As of ' . now()->format('d M Y'))

@php $total = collect($invoices)->sum('balance_amount'); @endphp

@section('body')
<table class="data">
    <tr>
        <th>Invoice #</th>
        <th>Vendor</th>
        <th>Date</th>
        <th class="num">Days</th>
        <th class="num">Balance</th>
        <th>Bucket</th>
    </tr>
    @forelse($invoices as $inv)
    <tr>
        <td>{{ $inv['invoice_number'] }}</td>
        <td>{{ $inv['party']['name'] ?? '—' }}</td>
        <td>{{ \Carbon\Carbon::parse($inv['invoice_date'])->format('d M Y') }}</td>
        <td class="num">{{ $inv['days_outstanding'] }}</td>
        <td class="num">₹{{ number_format($inv['balance_amount'], 2) }}</td>
        <td>{{ $inv['bucket'] }} days</td>
    </tr>
    @empty
    <tr><td colspan="6" class="muted">No outstanding payables.</td></tr>
    @endforelse
    <tr class="grand"><td colspan="4">Total Outstanding</td><td class="num">₹{{ number_format($total, 2) }}</td><td></td></tr>
</table>
@endsection
