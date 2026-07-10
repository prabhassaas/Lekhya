<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
    .wrap { padding: 6px 4px; }
    .title { text-align: center; font-size: 15px; font-weight: bold; color: #1B2A4A; letter-spacing: 0.05em; margin-bottom: 2px; }
    .subtitle { text-align: center; color: #6b7280; font-size: 10px; margin-bottom: 14px; }
    .head { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .head td { vertical-align: top; width: 50%; padding: 0; }
    .brand { font-size: 18px; font-weight: bold; color: #1B2A4A; }
    .muted { color: #6b7280; font-size: 10px; line-height: 1.5; }
    .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 12px; }
    .label { color: #9ca3af; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; }
    .val { font-weight: bold; color: #111827; }
    table.meta { width: 100%; border-collapse: collapse; margin: 4px 0 16px; }
    table.meta td { padding: 3px 0; font-size: 10.5px; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.items th { background: #1B2A4A; color: #fff; font-size: 9px; text-transform: uppercase;
                     letter-spacing: 0.04em; text-align: left; padding: 7px 10px; }
    table.items td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; }
    .num { text-align: right; }
    table.tot { width: 45%; border-collapse: collapse; margin-left: 55%; margin-top: 10px; }
    table.tot td { padding: 5px 8px; font-size: 11px; }
    table.tot tr.grand td { font-weight: bold; color: #1B2A4A; font-size: 13px; border-top: 2px solid #1B2A4A; padding-top: 8px; }
    .sign { margin-top: 34px; text-align: right; }
    .sign .n { font-weight: bold; color: #1B2A4A; font-size: 13px; }
    .foot { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #e5e7eb;
            padding-top: 6px; color: #9ca3af; font-size: 9px; text-align: center; }
</style>
</head>
<body>
<div class="wrap">
    <div class="title">TAX INVOICE</div>
    <div class="subtitle">{{ $d['sample'] ? 'SAMPLE — for preview only' : 'Original for Recipient' }}</div>

    <table class="head">
        <tr>
            <td>
                <div class="brand">ल {{ $d['seller']['name'] }}</div>
                <div class="muted">
                    {{ $d['seller']['address'] }}<br>
                    @if($d['seller']['gstin'])GSTIN: {{ $d['seller']['gstin'] }}<br>@endif
                    {{ $d['seller']['email'] }}{{ $d['seller']['phone'] ? ' · ' . $d['seller']['phone'] : '' }}
                </div>
            </td>
            <td style="text-align: right;">
                <div class="label">Invoice No.</div>
                <div class="val">{{ $d['invoice_number'] }}</div>
                <div class="label" style="margin-top:6px;">Invoice Date</div>
                <div class="val">{{ $d['invoice_date'] }}</div>
            </td>
        </tr>
    </table>

    <div class="box">
        <div class="label">Billed To</div>
        <div class="val" style="margin-top:2px;">{{ $d['buyer']['name'] }}</div>
        <div class="muted">
            {{ $d['buyer']['address'] }}<br>
            @if($d['buyer']['gstin'])GSTIN: {{ $d['buyer']['gstin'] }} · @endif Client ID: {{ $d['buyer']['client_id'] }}
        </div>
    </div>

    <table class="meta">
        <tr>
            <td>@if($d['payment_id'])<span class="label">Payment ID:</span> {{ $d['payment_id'] }}@endif</td>
            <td style="text-align:right;"><span class="label">Billing:</span> {{ $d['cycle'] }}</td>
        </tr>
    </table>

    <table class="items">
        <tr>
            <th>Description</th>
            <th style="width:70px;">SAC</th>
            <th class="num" style="width:90px;">Amount</th>
        </tr>
        <tr>
            <td>{{ $d['plan_name'] }} — subscription ({{ $d['cycle'] }})</td>
            <td>{{ $d['seller']['sac'] }}</td>
            <td class="num">₹{{ number_format($d['amount'], 2) }}</td>
        </tr>
    </table>

    <table class="tot">
        <tr><td>Taxable Value</td><td class="num">₹{{ number_format($d['amount'], 2) }}</td></tr>
        @if($d['interstate'])
        <tr><td>IGST @ {{ (int) $d['gst_rate'] }}%</td><td class="num">₹{{ number_format($d['igst'], 2) }}</td></tr>
        @else
        <tr><td>CGST @ {{ $d['gst_rate'] / 2 }}%</td><td class="num">₹{{ number_format($d['cgst'], 2) }}</td></tr>
        <tr><td>SGST @ {{ $d['gst_rate'] / 2 }}%</td><td class="num">₹{{ number_format($d['sgst'], 2) }}</td></tr>
        @endif
        <tr class="grand"><td>Total</td><td class="num">₹{{ number_format($d['total'], 2) }}</td></tr>
    </table>

    <div class="sign">
        <div class="muted">For {{ $d['seller']['name'] }}</div>
        <div class="n" style="margin-top:20px;">ल {{ $d['seller']['name'] }}</div>
        <div class="muted">Authorised Signatory</div>
    </div>

    <div class="foot">{{ $d['seller']['name'] }} · {{ $d['seller']['website'] }} — This is a computer-generated tax invoice.</div>
</div>
</body>
</html>
