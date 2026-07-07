@extends('layouts.marketing')
@section('title', 'Getting Started with Lekhya — Help Center')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <nav class="text-sm text-gray-500 mb-8">
        <a href="{{ route('marketing.help') }}" class="hover:text-navy-600">Help</a> → <span class="text-gray-900">Getting Started</span>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 mb-4">Getting Started with Lekhya</h1>
    <p class="text-lg text-gray-600 mb-10">You're 4 steps away from having GST-compliant accounts. No CA degree needed.</p>

    {{-- Progress steps --}}
    <div class="flex items-center gap-2 mb-12 overflow-x-auto pb-2">
        @foreach(['Create Account','Set Up Company','Add Parties','First Invoice'] as $i => $step)
        <div class="flex items-center gap-2 flex-shrink-0">
            <div class="w-8 h-8 rounded-full bg-navy-600 text-white text-sm font-bold flex items-center justify-center">{{ $i+1 }}</div>
            <span class="text-sm font-medium text-gray-700">{{ $step }}</span>
            @if($i < 3)<div class="w-8 h-px bg-gray-300"></div>@endif
        </div>
        @endforeach
    </div>

    <div class="space-y-12">

        {{-- Step 1 --}}
        <section id="step-1">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-navy-600 flex items-center justify-center text-white font-bold flex-shrink-0">1</div>
                <h2 class="text-2xl font-bold text-gray-900">Create Your Account</h2>
            </div>
            <div class="pl-13 space-y-4">
                <p class="text-gray-600">Go to <strong>lekhya.prabhassaas.in/register</strong> and sign up with your Google account or email. You get a <strong>14-day free trial</strong> — no credit card needed.</p>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <h4 class="font-semibold text-green-900 mb-2"><i class="fa fa-google mr-1"></i> Google Sign-In (Recommended)</h4>
                        <p class="text-sm text-green-800">Click "Continue with Google" — no password to remember. Uses the same Google account you use for Gmail.</p>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <h4 class="font-semibold text-blue-900 mb-2"><i class="fa fa-envelope mr-1"></i> Email & Password</h4>
                        <p class="text-sm text-blue-800">Fill in your name, email, and a password (min 8 characters). You can add your company GSTIN later.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Step 2 --}}
        <section id="step-2">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-navy-600 flex items-center justify-center text-white font-bold flex-shrink-0">2</div>
                <h2 class="text-2xl font-bold text-gray-900">Set Up Your Company</h2>
            </div>
            <div class="space-y-4">
                <p class="text-gray-600">After signing in, go to <strong>Settings → Company</strong>. Fill in:</p>
                <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100">
                    @foreach([
                        ['Company / Trade Name', 'Your registered business name as it appears on GST certificate', 'required'],
                        ['GSTIN', '15-digit GST Identification Number (e.g. 29ABCDE1234F1Z5)', 'required for GST filing'],
                        ['Business Type', 'Proprietorship, Partnership, Pvt Ltd, etc.', 'optional'],
                        ['State', 'Used to calculate CGST+SGST vs IGST automatically', 'required'],
                        ['Address', 'Registered business address', 'optional'],
                        ['Fiscal Year', 'April–March is default for India. Change if needed.', 'pre-filled'],
                    ] as [$field, $desc, $tag])
                    <div class="flex items-start gap-4 px-5 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 text-sm">{{ $field }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $desc }}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $tag === 'required' ? 'bg-red-50 text-red-600' : ($tag === 'pre-filled' ? 'bg-gray-100 text-gray-500' : 'bg-amber-50 text-amber-700') }} flex-shrink-0">{{ $tag }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Step 3 --}}
        <section id="step-3">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-navy-600 flex items-center justify-center text-white font-bold flex-shrink-0">3</div>
                <h2 class="text-2xl font-bold text-gray-900">Add Customers & Vendors</h2>
            </div>
            <div class="space-y-4">
                <p class="text-gray-600">Go to <strong>Accounting → Accounts</strong>. Lekhya comes with a full Indian Chart of Accounts pre-loaded. You just need to add your customers and vendors as <em>parties</em>.</p>
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <p class="font-semibold text-gray-800 mb-3">Click "New Account" and fill in:</p>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex gap-2"><i class="fa fa-circle-dot text-navy-600 mt-0.5 text-xs"></i><span><strong>Name</strong> — customer or vendor name</span></li>
                        <li class="flex gap-2"><i class="fa fa-circle-dot text-navy-600 mt-0.5 text-xs"></i><span><strong>Type</strong> — choose "Sundry Debtor" for customers, "Sundry Creditor" for vendors</span></li>
                        <li class="flex gap-2"><i class="fa fa-circle-dot text-navy-600 mt-0.5 text-xs"></i><span><strong>GSTIN</strong> — their GST number (used on invoices and e-invoice)</span></li>
                        <li class="flex gap-2"><i class="fa fa-circle-dot text-navy-600 mt-0.5 text-xs"></i><span><strong>State</strong> — determines IGST vs CGST+SGST on their invoices</span></li>
                    </ul>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <p class="text-sm text-amber-800"><i class="fa fa-lightbulb mr-1 text-amber-600"></i><strong>Tip:</strong> If you use SeedhaBill, your customers sync automatically — you won't need to add them manually.</p>
                </div>
            </div>
        </section>

        {{-- Step 4 --}}
        <section id="step-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-navy-600 flex items-center justify-center text-white font-bold flex-shrink-0">4</div>
                <h2 class="text-2xl font-bold text-gray-900">Create Your First Invoice</h2>
            </div>
            <div class="space-y-4">
                <p class="text-gray-600">Go to <strong>Accounting → Invoices → New Invoice</strong>. Lekhya fills in the GST automatically once you select the customer and line items.</p>
                <div class="grid sm:grid-cols-3 gap-4">
                    @foreach([
                        ['1. Pick Customer', 'Select from your party list. GST rate calculates instantly.', 'user'],
                        ['2. Add Line Items', 'Name, quantity, rate, HSN/SAC code, and GST rate per item.', 'list'],
                        ['3. Save & Post', '"Save Draft" keeps editing. "Post to Ledger" locks the invoice and creates accounting entries.', 'check'],
                    ] as [$title, $desc, $icon])
                    <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
                        <div class="w-10 h-10 bg-navy-50 rounded-lg flex items-center justify-center mx-auto mb-3"><i class="fa fa-{{ $icon }} text-navy-600"></i></div>
                        <h4 class="font-semibold text-gray-900 text-sm mb-1">{{ $title }}</h4>
                        <p class="text-xs text-gray-500">{{ $desc }}</p>
                    </div>
                    @endforeach
                </div>
                <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                    <p class="text-sm text-green-800"><i class="fa fa-circle-check mr-1 text-green-600"></i>Once posted, the invoice appears in your GST reports (GSTR-1) automatically. You can download the PDF or email it to the customer.</p>
                </div>
            </div>
        </section>

        {{-- What's next --}}
        <section class="bg-navy-600 rounded-2xl p-8 text-white">
            <h2 class="text-xl font-bold mb-4">What to explore next</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach([
                    ['Bank Reconciliation', 'Match bank transactions with your books', route('marketing.help.topic', 'bank-reconciliation')],
                    ['GST Filing', 'Generate and file GSTR-1, 3B, 2B', route('marketing.help.topic', 'gst-api')],
                    ['SeedhaBill Connector', 'Auto-import invoices from SeedhaBill', route('marketing.help.topic', 'seedha-bill')],
                    ['AI Features', 'Let AI extract invoices and suggest accounts', route('marketing.help.topic', 'local-llm')],
                ] as [$title, $desc, $url])
                <a href="{{ $url }}" class="bg-white bg-opacity-10 hover:bg-opacity-20 rounded-xl p-3 transition">
                    <p class="font-semibold text-sm">{{ $title }}</p>
                    <p class="text-xs text-blue-200 mt-0.5">{{ $desc }}</p>
                </a>
                @endforeach
            </div>
        </section>

    </div>
</div>
@endsection
