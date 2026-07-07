@extends('layouts.marketing')
@section('title', 'Lekhya Pramaan — CA Edition Help')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <nav class="text-sm text-gray-500 mb-8">
        <a href="{{ route('marketing.help') }}" class="hover:text-navy-600">Help</a> → <span class="text-gray-900">Lekhya Pramaan</span>
    </nav>

    {{-- Hero --}}
    <div class="flex items-start gap-4 mb-6">
        <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center flex-shrink-0">
            <i class="fa fa-certificate text-amber-600 text-2xl"></i>
        </div>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-1">Lekhya Pramaan</h1>
            <span class="inline-block bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1 rounded-full">CA / Auditor Edition</span>
        </div>
    </div>
    <p class="text-lg text-gray-600 mb-10">Practice management tools built for Chartered Accountants — UDIN, audit reports, DSC, and compliance calendar in one place.</p>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-10">
        <h3 class="font-semibold text-amber-900 mb-1"><i class="fa fa-lock mr-2"></i>Pramaan is a CA-only add-on</h3>
        <p class="text-sm text-amber-800">These features are only available on the <strong>Pramaan plan</strong>. If you're a CA firm or practicing chartered accountant, <a href="{{ route('marketing.contact') }}" class="underline font-medium">contact us</a> to enable Pramaan on your account.</p>
    </div>

    <div class="space-y-12">

        {{-- UDIN --}}
        <section>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <i class="fa fa-fingerprint text-amber-600"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">UDIN Tracking</h2>
            </div>
            <p class="text-gray-600 mb-4">UDIN (Unique Document Identification Number) is mandatory for CAs signing audit reports and certificates. Lekhya Pramaan lets you generate, log, and retrieve UDINs without leaving your accounting software.</p>
            <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100">
                @foreach([
                    ['Generate UDIN', 'Enter your ICAI membership number, the document type, and key financials. Lekhya formats and logs the UDIN request.'],
                    ['Retrieve UDIN', 'Search by client name, date range, or document type. View all UDINs issued from your account.'],
                    ['Revoke UDIN', 'Mark a UDIN as revoked with a reason — keeps a full audit trail.'],
                    ['UDIN Register', 'Export the complete UDIN register as PDF or Excel for ICAI compliance records.'],
                ] as [$feature, $desc])
                <div class="flex gap-4 px-5 py-4">
                    <i class="fa fa-circle-check text-amber-500 mt-0.5 flex-shrink-0 text-sm"></i>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $feature }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        {{-- Audit Reports --}}
        <section>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fa fa-file-signature text-purple-600"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Audit Reports</h2>
            </div>
            <p class="text-gray-600 mb-4">Generate and store audit reports directly linked to your client's Lekhya data. No more switching between Word and Tally.</p>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach([
                    ['Tax Audit (3CD)', 'Auto-populates clauses from the client\'s ledger data. CA reviews and certifies.', 'file-contract'],
                    ['Statutory Audit Report', 'Standard format with observations, notes, and management response sections.', 'landmark'],
                    ['Internal Audit Report', 'Customizable format — add findings, risk ratings, and recommendations.', 'magnifying-glass'],
                    ['Compilation Report', 'For non-audit engagements — compiles financial statements from the books.', 'layer-group'],
                ] as [$type, $desc, $icon])
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <i class="fa fa-{{ $icon }} text-purple-600 mb-2 block"></i>
                    <p class="font-semibold text-gray-900 text-sm">{{ $type }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </section>

        {{-- Compliance Calendar --}}
        <section>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fa fa-calendar-check text-green-600"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Compliance Calendar</h2>
            </div>
            <p class="text-gray-600 mb-4">Never miss a filing deadline for any of your clients. The calendar pulls due dates from ICAI, GST, and MCA and maps them to your client list.</p>
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="grid grid-cols-3 bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 px-5 py-3">
                    <span>Due Date</span>
                    <span>Filing / Task</span>
                    <span>Status</span>
                </div>
                @foreach([
                    ['7th of month', 'TDS Payment', 'Recurring'],
                    ['15th of month', 'PF/ESI Payment', 'Recurring'],
                    ['20th of month', 'GSTR-3B Filing', 'Recurring'],
                    ['11th of month', 'GSTR-1 Filing', 'Recurring'],
                    ['30 Sep', 'Income Tax Return (Audit Cases)', 'Annual'],
                    ['31 Oct', 'Tax Audit Report Filing', 'Annual'],
                ] as [$date, $task, $type])
                <div class="grid grid-cols-3 px-5 py-3 border-b border-gray-50 hover:bg-gray-50 text-sm">
                    <span class="text-gray-900 font-medium">{{ $date }}</span>
                    <span class="text-gray-700">{{ $task }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $type === 'Annual' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' }} w-fit">{{ $type }}</span>
                </div>
                @endforeach
            </div>
            <p class="text-sm text-gray-500 mt-3">Assign tasks to team members, mark as completed, and get email/WhatsApp reminders 3 days before each deadline.</p>
        </section>

        {{-- Client Management --}}
        <section>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fa fa-users text-blue-600"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Client Management</h2>
            </div>
            <p class="text-gray-600 mb-4">Manage all your client companies from a single CA login. Each client has their own isolated accounting environment.</p>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach([
                    ['Switch between clients instantly', 'No separate logins — one CA account, multiple client books.'],
                    ['Assign team members', 'Add your articles and staff to specific client accounts with view-only or edit roles.'],
                    ['Client-wise billing', 'Track time and bill clients directly from Lekhya (CA Edition billing module).'],
                    ['Document vault', 'Store client PAN, Aadhar, GST certificate, and engagement letters securely.'],
                ] as [$feat, $desc])
                <div class="flex gap-3 bg-white border border-gray-200 rounded-xl p-4">
                    <i class="fa fa-circle-check text-blue-500 mt-0.5 flex-shrink-0 text-sm"></i>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $feat }}</p>
                        <p class="text-xs text-gray-500">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        {{-- Get Pramaan --}}
        <section class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-2xl p-8 text-center">
            <div class="w-16 h-16 bg-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fa fa-certificate text-white text-2xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Enable Pramaan for Your Practice</h2>
            <p class="text-gray-600 text-sm mb-6">Pramaan is available for practicing CAs with an ICAI membership number. Get started with a 14-day free trial.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('marketing.contact') }}"
                   class="bg-amber-600 hover:bg-amber-700 text-white font-semibold px-6 py-3 rounded-xl transition text-sm">
                    Request Pramaan Access
                </a>
                <a href="{{ route('marketing.pricing') }}"
                   class="border border-amber-300 text-amber-800 hover:bg-amber-50 font-medium px-6 py-3 rounded-xl transition text-sm">
                    View CA Pricing
                </a>
            </div>
        </section>

    </div>
</div>
@endsection
