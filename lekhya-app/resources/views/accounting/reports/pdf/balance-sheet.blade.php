@extends('accounting.reports.pdf.layout')
@section('report_title', 'Balance Sheet')
@section('report_period', 'As of ' . \Carbon\Carbon::parse($asOf)->format('d M Y'))

@section('body')
<table class="data">
    <tr class="sec"><td colspan="2">Assets</td></tr>
    @forelse($assets as $a)
    <tr><td>{{ $a['account']->name }}</td><td class="num">₹{{ number_format($a['net'], 2) }}</td></tr>
    @empty
    <tr><td colspan="2" class="muted">No asset accounts.</td></tr>
    @endforelse
    <tr class="grand"><td>Total Assets</td><td class="num">₹{{ number_format($totalAssets, 2) }}</td></tr>
</table>

<table class="data" style="margin-top: 18px;">
    <tr class="sec"><td colspan="2">Liabilities</td></tr>
    @forelse($liabilities as $l)
    <tr><td>{{ $l['account']->name }}</td><td class="num">₹{{ number_format($l['net'], 2) }}</td></tr>
    @empty
    <tr><td colspan="2" class="muted">No liability accounts.</td></tr>
    @endforelse
    <tr class="tot"><td>Total Liabilities</td><td class="num">₹{{ number_format($totalLiabilities, 2) }}</td></tr>

    <tr class="sec"><td colspan="2">Equity</td></tr>
    @foreach($equity as $e)
    <tr><td>{{ $e['account']->name }}</td><td class="num">₹{{ number_format($e['net'], 2) }}</td></tr>
    @endforeach
    <tr><td>Current Year Earnings</td><td class="num">₹{{ number_format($currentEarnings, 2) }}</td></tr>
    <tr class="tot"><td>Total Equity</td><td class="num">₹{{ number_format($totalEquity, 2) }}</td></tr>

    <tr class="grand"><td>Total Liabilities + Equity</td><td class="num">₹{{ number_format($totalLiabilities + $totalEquity, 2) }}</td></tr>
</table>

@if(abs($totalAssets - ($totalLiabilities + $totalEquity)) > 0.01)
<div class="warn">Assets (₹{{ number_format($totalAssets, 2) }}) do not equal Liabilities + Equity (₹{{ number_format($totalLiabilities + $totalEquity, 2) }}). Check for unposted opening balances or unbalanced journals.</div>
@endif
@endsection
