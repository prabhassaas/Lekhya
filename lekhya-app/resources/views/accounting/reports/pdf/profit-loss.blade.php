@extends('accounting.reports.pdf.layout')
@section('report_title', 'Profit & Loss Statement')
@section('report_period', 'Period: ' . \Carbon\Carbon::parse($from)->format('d M Y') . ' to ' . \Carbon\Carbon::parse($to)->format('d M Y'))

@section('body')
<table class="data">
    <tr class="sec"><td colspan="2">Revenue</td></tr>
    @forelse($revenues as $r)
    <tr><td>{{ $r['account']->name }}</td><td class="num">₹{{ number_format($r['net'], 2) }}</td></tr>
    @empty
    <tr><td colspan="2" class="muted">No revenue recorded.</td></tr>
    @endforelse
    <tr class="tot"><td>Total Revenue</td><td class="num">₹{{ number_format($totalRevenue, 2) }}</td></tr>

    @if(count($cogs))
    <tr class="sec"><td colspan="2">Cost of Sales</td></tr>
    @foreach($cogs as $c)
    <tr><td>{{ $c['account']->name }}</td><td class="num">₹{{ number_format($c['net'], 2) }}</td></tr>
    @endforeach
    <tr class="tot"><td>Gross Profit</td><td class="num">₹{{ number_format($grossProfit, 2) }}</td></tr>
    @endif

    <tr class="sec"><td colspan="2">Operating Expenses</td></tr>
    @forelse($expenses as $e)
    @continue(($e['account']->sub_type ?? null) === 'cost_of_sales')
    <tr><td>{{ $e['account']->name }}</td><td class="num">₹{{ number_format($e['net'], 2) }}</td></tr>
    @empty
    <tr><td colspan="2" class="muted">No expenses recorded.</td></tr>
    @endforelse
    <tr class="tot"><td>Total Operating Expenses</td><td class="num">₹{{ number_format($totalExpenses, 2) }}</td></tr>

    <tr class="grand"><td>Net Profit</td><td class="num">₹{{ number_format($netProfit, 2) }}</td></tr>
</table>
@endsection
