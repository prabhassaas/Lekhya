@extends('accounting.reports.pdf.layout')
@section('report_title', 'Party Statement' . ($party ? ' — ' . $party->name : ''))
@section('report_period', \Carbon\Carbon::parse($from)->format('d M Y') . ' — ' . \Carbon\Carbon::parse($to)->format('d M Y'))

@section('body')
@if(!empty($party->gstin))<div class="muted" style="margin-bottom:6px;">GSTIN: {{ $party->gstin }}</div>@endif
<table class="data">
    <tr>
        <th style="width: 62px;">Date</th>
        <th style="width: 90px;">Reference</th>
        <th>Particulars</th>
        <th class="num">Debit</th>
        <th class="num">Credit</th>
        <th class="num">Balance</th>
    </tr>
    @forelse($rows as $r)
    <tr>
        <td class="muted">{{ \Carbon\Carbon::parse($r['date'])->format('d M Y') }}</td>
        <td>{{ $r['ref'] }}</td>
        <td>{{ $r['particulars'] }}</td>
        <td class="num">{{ $r['debit'] > 0 ? '₹' . number_format($r['debit'], 2) : '—' }}</td>
        <td class="num">{{ $r['credit'] > 0 ? '₹' . number_format($r['credit'], 2) : '—' }}</td>
        <td class="num">₹{{ number_format(abs($r['balance']), 2) }} {{ $r['balance'] >= 0 ? 'Dr' : 'Cr' }}</td>
    </tr>
    @empty
    <tr><td colspan="6" class="muted" style="text-align:center; padding:14px;">No transactions for this party in the period.</td></tr>
    @endforelse
    @if($rows->isNotEmpty())
    <tr class="grand">
        <td colspan="5">Closing balance</td>
        <td class="num">₹{{ number_format(abs($rows->last()['balance']), 2) }} {{ $rows->last()['balance'] >= 0 ? 'Dr' : 'Cr' }}</td>
    </tr>
    @endif
</table>
@endsection
