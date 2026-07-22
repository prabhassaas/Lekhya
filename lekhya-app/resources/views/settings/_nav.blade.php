@php
    $tabs = [
        ['settings.company', 'Company', 'fa-building'],
        ['settings.fiscal_years', 'Fiscal Years', 'fa-calendar'],
        ['settings.users', 'Users & Roles', 'fa-users'],
        ['settings.security', 'Security', 'fa-shield-halved'],
        ['settings.audit_trail', 'Audit Trail', 'fa-clipboard-list'],
        ['settings.billing', 'Billing', 'fa-credit-card'],
    ];
@endphp
<div class="border-b border-gray-200 mb-6">
    <nav class="flex flex-wrap gap-1 -mb-px">
        @foreach($tabs as [$route, $label, $icon])
        <a href="{{ route($route) }}"
           class="px-4 py-2.5 text-sm font-medium border-b-2 {{ request()->routeIs($route) ? 'border-navy-600 text-navy-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <i class="fa {{ $icon }} mr-1.5"></i>{{ $label }}
        </a>
        @endforeach
    </nav>
</div>
