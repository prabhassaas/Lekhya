@extends('accounting.reports.pdf.layout')
@section('report_title', 'Trial Balance')
@section('report_period', 'As of ' . \Carbon\Carbon::parse($asOf)->format('d M Y'))

@php
    $totalDebit = collect($accounts)->sum(fn($a) => $a['net'] > 0 ? $a['net'] : 0);
    $totalCredit = collect($accounts)->sum(fn($a) => $a['net'] < 0 ? abs($a['net']) : 0);
@endphp

@section('body')
<table class="data">
    <tr>
        <th style="width: 60px;">Code</th>
        <th>Account</th>
        <th>Type</th>
        <th class="num">Debit</th>
        <th class="num">Credit</th>
    </tr>
    @foreach($accounts as $acc)
    @continue($acc['net'] == 0)
    <tr>
        <td class="muted">{{ $acc['code'] }}</td>
        <td>{{ $acc['name'] }}</td>
        <td style="text-transform: capitalize;">{{ $acc['type'] }}</td>
        <td class="num">{{ $acc['net'] > 0 ? '₹' . number_format($acc['net'], 2) : '—' }}</td>
        <td class="num">{{ $acc['net'] < 0 ? '₹' . number_format(abs($acc['net']), 2) : '—' }}</td>
    </tr>
    @endforeach
    <tr class="grand">
        <td colspan="3">Total</td>
        <td class="num">₹{{ number_format($totalDebit, 2) }}</td>
        <td class="num">₹{{ number_format($totalCredit, 2) }}</td>
    </tr>
</table>

@if(abs($totalDebit - $totalCredit) > 0.01)
<div class="warn">Trial balance does not balance — debit and credit totals differ by ₹{{ number_format(abs($totalDebit - $totalCredit), 2) }}.</div>
@endif
@endsection
