@extends('accounting.reports.pdf.layout')
@section('report_title', 'GST Summary')
@section('report_period', \Carbon\Carbon::parse($from)->format('d M Y') . ' — ' . \Carbon\Carbon::parse($to)->format('d M Y'))

@section('body')
<table class="data">
    <tr>
        <th></th>
        <th class="num">Taxable</th>
        <th class="num">CGST</th>
        <th class="num">SGST</th>
        <th class="num">IGST</th>
        <th class="num">Total Tax</th>
    </tr>
    <tr>
        <td class="sec">Output tax (Sales)</td>
        <td class="num">₹{{ number_format($output['taxable'], 2) }}</td>
        <td class="num">₹{{ number_format($output['cgst'], 2) }}</td>
        <td class="num">₹{{ number_format($output['sgst'], 2) }}</td>
        <td class="num">₹{{ number_format($output['igst'], 2) }}</td>
        <td class="num">₹{{ number_format($output['total'], 2) }}</td>
    </tr>
    <tr>
        <td class="sec">Input tax credit (Purchases)</td>
        <td class="num">₹{{ number_format($input['taxable'], 2) }}</td>
        <td class="num">₹{{ number_format($input['cgst'], 2) }}</td>
        <td class="num">₹{{ number_format($input['sgst'], 2) }}</td>
        <td class="num">₹{{ number_format($input['igst'], 2) }}</td>
        <td class="num">₹{{ number_format($input['total'], 2) }}</td>
    </tr>
    <tr class="grand">
        <td colspan="5">Net GST {{ $net >= 0 ? 'payable' : 'credit (carry forward)' }}</td>
        <td class="num">₹{{ number_format(abs($net), 2) }}</td>
    </tr>
</table>
<div class="muted" style="margin-top:10px;">Net = Output tax − Input tax credit. A negative figure is credit carried forward, not payable.</div>
@endsection
