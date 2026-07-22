@extends('accounting.reports.pdf.layout')
@section('report_title', 'Day Book')
@section('report_period', \Carbon\Carbon::parse($from)->format('d M Y') . ' — ' . \Carbon\Carbon::parse($to)->format('d M Y'))

@section('body')
<table class="data">
    <tr>
        <th style="width: 62px;">Date</th>
        <th style="width: 90px;">Voucher</th>
        <th>Type</th>
        <th>Narration</th>
        <th class="num">Amount</th>
    </tr>
    @forelse($journals as $j)
    <tr>
        <td class="muted">{{ \Carbon\Carbon::parse($j->date)->format('d M Y') }}</td>
        <td>{{ $j->voucher_number }}</td>
        <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $j->voucher_type) }}</td>
        <td>{{ \Illuminate\Support\Str::limit($j->narration, 60) }}</td>
        <td class="num">₹{{ number_format((float) $j->total_debit, 2) }}</td>
    </tr>
    @empty
    <tr><td colspan="5" class="muted" style="text-align:center; padding:14px;">No posted vouchers in this period.</td></tr>
    @endforelse
    <tr class="grand">
        <td colspan="4">Total</td>
        <td class="num">₹{{ number_format((float) $totalDebit, 2) }}</td>
    </tr>
</table>
@endsection
