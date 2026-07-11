<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
    .wrap { padding: 4px 2px; }
    .head { border-bottom: 2px solid #1B2A4A; padding-bottom: 10px; margin-bottom: 16px; }
    .head td { vertical-align: top; }
    .brand { font-size: 20px; font-weight: bold; color: #1B2A4A; }
    .brand span { color: #2e5a94; }
    .org { font-size: 15px; font-weight: bold; color: #111827; }
    .muted { color: #6b7280; font-size: 10px; }
    .rtitle { font-size: 14px; font-weight: bold; color: #1B2A4A; margin-bottom: 2px; }
    table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.data th { background: #f3f4f6; color: #6b7280; text-transform: uppercase; font-size: 8.5px;
                    letter-spacing: 0.04em; text-align: left; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
    table.data td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; }
    .num { text-align: right; }
    .sec { font-weight: bold; color: #111827; background: #f9fafb; }
    .tot td { font-weight: bold; border-top: 2px solid #d1d5db; border-bottom: none; padding-top: 8px; }
    .grand td { font-weight: bold; color: #1B2A4A; font-size: 12px; border-top: 2px solid #1B2A4A; }
    .warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 8px 10px;
            border-radius: 4px; margin-top: 12px; font-size: 10px; }
    .foot { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #e5e7eb;
            padding-top: 6px; color: #9ca3af; font-size: 9px; }
    .foot td { vertical-align: bottom; }
</style>
</head>
<body>
<div class="wrap">
    <table class="head" width="100%">
        <tr>
            <td>
                <div class="org">{{ $tenant->name ?? 'Company' }}</div>
                @if(!empty($tenant->gstin))<div class="muted">GSTIN: {{ $tenant->gstin }}</div>@endif
                @if(!empty($tenant->city))<div class="muted">{{ $tenant->city }}{{ $tenant->state ? ', ' . $tenant->state : '' }}</div>@endif
            </td>
            <td style="text-align: right;">
                <div class="brand"><img src="{{ public_path('logo-badge.png') }}" style="width:16px;height:16px;vertical-align:-3px;margin-right:4px"> Lekhya<span> AI ERP</span></div>
                <div class="muted">Generated {{ ($generatedAt ?? now())->format('d M Y, H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="rtitle">@yield('report_title')</div>
    <div class="muted">@yield('report_period')</div>

    @yield('body')

    <table class="foot" width="100%">
        <tr>
            <td>Powered by Lekhya — a Prabhas SaaS product</td>
            <td style="text-align: right;">This is a system-generated report.</td>
        </tr>
    </table>
</div>
</body>
</html>
