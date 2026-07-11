<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
<tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
        <!-- Header -->
        <tr><td style="background:#1B2A4A;padding:22px 28px;">
            <img src="{{ config('app.url') }}/logo-badge.png" width="20" height="20" style="vertical-align:-4px;margin-right:6px" alt=""><span style="color:#ffffff;font-size:20px;font-weight:bold;">{{ config('prabhas.name') }}</span>
        </td></tr>

        <!-- Body -->
        <tr><td style="padding:28px;">
            @if($d['sample'])
            <div style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;padding:8px 12px;border-radius:6px;font-size:13px;margin-bottom:16px;">
                This is a <strong>sample</strong> invoice email to preview the format.
            </div>
            @endif

            <p style="font-size:15px;margin:0 0 6px;">Dear {{ $d['buyer']['name'] }},</p>
            <p style="font-size:14px;line-height:1.6;color:#374151;margin:0 0 18px;">
                Thank you for subscribing to <strong>{{ $d['plan_name'] }}</strong>. Your payment has been received and your
                GST tax invoice is attached as a PDF. A summary is below.
            </p>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;font-size:14px;">
                <tr><td style="padding:10px 14px;color:#6b7280;border-bottom:1px solid #f3f4f6;">Invoice #</td><td style="padding:10px 14px;text-align:right;font-weight:bold;border-bottom:1px solid #f3f4f6;">{{ $d['invoice_number'] }}</td></tr>
                <tr><td style="padding:10px 14px;color:#6b7280;border-bottom:1px solid #f3f4f6;">Date</td><td style="padding:10px 14px;text-align:right;border-bottom:1px solid #f3f4f6;">{{ $d['invoice_date'] }}</td></tr>
                <tr><td style="padding:10px 14px;color:#6b7280;border-bottom:1px solid #f3f4f6;">Client ID</td><td style="padding:10px 14px;text-align:right;border-bottom:1px solid #f3f4f6;">{{ $d['buyer']['client_id'] }}</td></tr>
                @if($d['payment_id'])
                <tr><td style="padding:10px 14px;color:#6b7280;border-bottom:1px solid #f3f4f6;">Payment ID</td><td style="padding:10px 14px;text-align:right;border-bottom:1px solid #f3f4f6;">{{ $d['payment_id'] }}</td></tr>
                @endif
                <tr><td style="padding:10px 14px;color:#6b7280;border-bottom:1px solid #f3f4f6;">Plan</td><td style="padding:10px 14px;text-align:right;border-bottom:1px solid #f3f4f6;">{{ $d['plan_name'] }} ({{ $d['cycle'] }})</td></tr>
                <tr><td style="padding:10px 14px;color:#6b7280;">Taxable</td><td style="padding:10px 14px;text-align:right;">&#8377;{{ number_format($d['amount'], 2) }}</td></tr>
                <tr><td style="padding:8px 14px;color:#6b7280;">GST @ {{ (int) $d['gst_rate'] }}%</td><td style="padding:8px 14px;text-align:right;">&#8377;{{ number_format($d['gst_total'], 2) }}</td></tr>
                <tr><td style="padding:12px 14px;font-weight:bold;color:#1B2A4A;border-top:2px solid #1B2A4A;">Total Paid</td><td style="padding:12px 14px;text-align:right;font-weight:bold;font-size:16px;color:#1B2A4A;border-top:2px solid #1B2A4A;">&#8377;{{ number_format($d['total'], 2) }}</td></tr>
            </table>

            <!-- AI setup note -->
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 16px;margin-top:22px;">
                <p style="margin:0;font-size:13px;color:#1e40af;line-height:1.6;">
                    <strong>Want AI features (invoice OCR, smart suggestions)?</strong><br>
                    Please contact our admin to configure the AI for your account — reach us at
                    <a href="mailto:{{ config('prabhas.email') }}" style="color:#1d4ed8;">{{ config('prabhas.email') }}</a>.
                </p>
            </div>

            <!-- Signature -->
            <div style="margin-top:28px;padding-top:18px;border-top:1px solid #e5e7eb;">
                <p style="margin:0;font-size:14px;color:#374151;">Warm regards,</p>
                <p style="margin:6px 0 0;font-size:15px;font-weight:bold;color:#1B2A4A;"><img src="{{ config('app.url') }}/logo-badge.png" width="16" height="16" style="vertical-align:-3px;margin-right:4px" alt="">{{ config('prabhas.name') }}</p>
                <p style="margin:2px 0 0;font-size:12px;color:#6b7280;">{{ config('prabhas.website') }} &middot; {{ config('prabhas.email') }}</p>
            </div>
        </td></tr>

        <!-- Footer -->
        <tr><td style="background:#f9fafb;padding:14px 28px;border-top:1px solid #e5e7eb;">
            <p style="margin:0;font-size:11px;color:#9ca3af;">This is a system-generated invoice from {{ config('prabhas.name') }}. Please retain it for your records.</p>
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
