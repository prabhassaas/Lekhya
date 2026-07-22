<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
<tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
        <tr><td style="background:#1B2A4A;padding:22px 28px;">
            <img src="{{ config('app.url') }}/logo-badge.png" width="20" height="20" style="vertical-align:-4px;margin-right:6px" alt=""><span style="color:#ffffff;font-size:20px;font-weight:bold;">{{ config('app.name') }}</span>
        </td></tr>

        <tr><td style="padding:28px;">
            <p style="font-size:15px;margin:0 0 6px;">Hi {{ $inviteeName }},</p>
            <p style="font-size:14px;line-height:1.6;color:#374151;margin:0 0 18px;">
                <strong>{{ $inviterName }}</strong> has invited you to join <strong>{{ $companyName }}</strong> on
                {{ config('app.name') }} as <strong>{{ $roleLabel }}</strong>. Set your password to activate your account.
            </p>

            @if($note)
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;">
                <tr><td style="background:#f9fafb;border-left:3px solid #1B2A4A;padding:10px 14px;font-size:13px;color:#4b5563;font-style:italic;">“{{ $note }}”</td></tr>
            </table>
            @endif

            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:6px 0 20px;">
                <tr><td style="border-radius:8px;background:#1B2A4A;">
                    <a href="{{ $acceptUrl }}" style="display:inline-block;padding:12px 26px;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:8px;">Accept invitation &amp; set password</a>
                </td></tr>
            </table>

            <p style="font-size:12px;line-height:1.6;color:#9ca3af;margin:0 0 4px;">
                Or paste this link into your browser:
            </p>
            <p style="font-size:12px;line-height:1.5;color:#2563eb;word-break:break-all;margin:0 0 18px;">{{ $acceptUrl }}</p>

            <p style="font-size:12px;line-height:1.6;color:#9ca3af;margin:0;border-top:1px solid #f3f4f6;padding-top:14px;">
                This invitation link expires in 7 days. If you weren't expecting it, you can safely ignore this email.
            </p>
        </td></tr>

        <tr><td style="background:#f9fafb;padding:16px 28px;border-top:1px solid #e5e7eb;">
            <p style="font-size:11px;color:#9ca3af;margin:0;">{{ config('app.name') }} — GST-compliant accounting for India.</p>
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
