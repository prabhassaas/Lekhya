<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
<tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
        <tr><td style="background:#1B2A4A;padding:22px 28px;">
            <span style="color:#ffffff;font-size:19px;font-weight:bold;">{{ $company }}</span>
        </td></tr>
        <tr><td style="padding:28px;">
            <p style="font-size:15px;margin:0 0 6px;">Hello,</p>
            <p style="font-size:14px;line-height:1.6;color:#374151;margin:0 0 16px;">
                Please find attached the <strong>{{ $title }}</strong>@if($period) for <strong>{{ $period }}</strong>@endif from {{ $company }}.
            </p>

            @if($note)
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
                <tr><td style="background:#f9fafb;border-left:3px solid #1B2A4A;padding:10px 14px;font-size:13px;color:#4b5563;">{{ $note }}</td></tr>
            </table>
            @endif

            <p style="font-size:13px;line-height:1.6;color:#6b7280;margin:0;">The report is attached as a PDF. If you have any questions, just reply to this email.</p>
        </td></tr>
        <tr><td style="background:#f9fafb;padding:14px 28px;border-top:1px solid #e5e7eb;">
            <p style="font-size:11px;color:#9ca3af;margin:0;">Sent via {{ config('app.name') }} — a Prabhas SaaS product.</p>
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
