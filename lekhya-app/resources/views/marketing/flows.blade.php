@extends('layouts.marketing')
@section('title', 'How Lekhya Works — Process Flows')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-16">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">How Lekhya Works</h1>
        <p class="text-xl text-gray-600">Module-wise flowcharts for Lekhya ERP and Lekhya Pramaan (CA edition)</p>
    </div>

    {{-- Tab navigation --}}
    <div x-data="{ tab: 'invoice-flow' }" class="space-y-8">
        <div class="flex flex-wrap gap-2 border-b border-gray-200">
            @foreach([
                ['invoice-flow', 'Seedha Bill → Lekhya'],
                ['accounting-flow', 'Invoice → Ledger → P&L'],
                ['gst-flow', 'GST Compliance Flow'],
                ['bank-flow', 'Bank Reconciliation'],
                ['ai-flow', 'AI Extraction Flow'],
                ['pramaan-flow', 'CA (Pramaan) Flow'],
                ['tally-flow', 'Tally Migration'],
            ] as [$id, $label])
            <button @click="tab='{{ $id }}'" :class="tab==='{{ $id }}' ? 'border-navy-600 text-navy-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Flow 1: Seedha Bill → Lekhya --}}
        <div x-show="tab==='invoice-flow'" class="flow-section">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Seedha Bill Invoice → Lekhya Ledger</h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="space-y-3">
                    <h3 class="font-semibold text-navy-600 text-lg mb-4">Mode A — Same Prabhas Account</h3>
                    @foreach([
                        ['1', 'Freelancer creates invoice in Seedha Bill', 'green'],
                        ['2', 'Toggle "Auto-sync to Lekhya" is ON in settings', 'green'],
                        ['3', 'Seedha Bill writes to shared Supabase table', 'blue'],
                        ['4', 'Lekhya RPC polls the shared table (real-time)', 'blue'],
                        ['5', 'Import Pipeline: normalize → dedupe → validate', 'purple'],
                        ['6', 'If valid → Post invoice + journal entry', 'green'],
                        ['7', 'Source invoice LOCKED (Posted status). No re-submission.', 'red'],
                        ['8', 'Accounts updated: DR Receivable, CR Sales, CR GST Output', 'navy'],
                    ] as [$n, $step, $color])
                    <div class="flex items-start space-x-3">
                        <div class="w-7 h-7 rounded-full bg-{{ $color }}-100 border-2 border-{{ $color }}-300 flex items-center justify-center text-{{ $color }}-700 font-bold text-sm flex-shrink-0">{{ $n }}</div>
                        <p class="text-gray-700 pt-0.5">{{ $step }}</p>
                    </div>
                    @endforeach
                </div>
                <div class="space-y-3">
                    <h3 class="font-semibold text-purple-600 text-lg mb-4">Mode B — Different Accounts (Token)</h3>
                    @foreach([
                        ['1', 'Accountant generates a Client Connection Token in Lekhya', 'purple'],
                        ['2', 'Token has: scope, expiry, label, seat allocation', 'purple'],
                        ['3', 'Freelancer pastes token in Seedha Bill → connection established', 'blue'],
                        ['4', 'Each connection = 1 client-seat on accountant\'s plan', 'orange'],
                        ['5', 'Seedha Bill webhook / REST API pushes invoices to Lekhya', 'blue'],
                        ['6', 'Same pipeline: normalize → dedupe → validate → post', 'green'],
                        ['7', 'Token revoked? Sync stops immediately.', 'red'],
                        ['8', 'Connector health dashboard shows per-connection status', 'navy'],
                    ] as [$n, $step, $color])
                    <div class="flex items-start space-x-3">
                        <div class="w-7 h-7 rounded-full bg-{{ $color }}-100 border-2 border-{{ $color }}-300 flex items-center justify-center text-{{ $color }}-700 font-bold text-sm flex-shrink-0">{{ $n }}</div>
                        <p class="text-gray-700 pt-0.5">{{ $step }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Quarantine flow --}}
            <div class="mt-8 p-6 bg-amber-50 border border-amber-200 rounded-xl">
                <h3 class="font-semibold text-amber-900 mb-3"><i class="fa fa-triangle-exclamation mr-2"></i>When validation fails → Quarantine Queue</h3>
                <div class="flex flex-wrap gap-4 text-sm">
                    @foreach(['Invalid GSTIN format', 'Missing invoice number', 'HSN code not found', 'Tax math mismatch'] as $reason)
                    <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full border border-amber-200">{{ $reason }}</span>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-amber-800">→ Goes to <strong>Import Queue</strong> for manual review. Accountant fixes and approves. Invalid invoices NEVER enter the ledger.</p>
            </div>
        </div>

        {{-- Flow 2: Invoice → Ledger → P&L --}}
        <div x-show="tab==='accounting-flow'" x-cloak class="flow-section">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Invoice → Double-Entry Journal → P&L / Balance Sheet</h2>

            <div class="space-y-6">
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h3 class="font-semibold text-navy-600 mb-4">Sales Invoice ₹10,000 + 18% GST (CGST+SGST)</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="bg-navy-50"><th class="text-left p-3 font-semibold text-navy-700">Account</th><th class="text-right p-3 font-semibold text-navy-700">Debit</th><th class="text-right p-3 font-semibold text-navy-700">Credit</th><th class="p-3 font-semibold text-navy-700">Why?</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr><td class="p-3 font-medium">Accounts Receivable (1100)</td><td class="p-3 text-right text-green-700 font-bold">₹11,800</td><td class="p-3 text-right">—</td><td class="p-3 text-gray-500">Customer owes us this</td></tr>
                                <tr class="bg-gray-50"><td class="p-3 font-medium">Sales Revenue (4000)</td><td class="p-3 text-right">—</td><td class="p-3 text-right text-blue-700 font-bold">₹10,000</td><td class="p-3 text-gray-500">Revenue earned</td></tr>
                                <tr><td class="p-3 font-medium">CGST Output Payable (2210)</td><td class="p-3 text-right">—</td><td class="p-3 text-right text-blue-700 font-bold">₹900</td><td class="p-3 text-gray-500">GST we collect for Govt</td></tr>
                                <tr class="bg-gray-50"><td class="p-3 font-medium">SGST Output Payable (2220)</td><td class="p-3 text-right">—</td><td class="p-3 text-right text-blue-700 font-bold">₹900</td><td class="p-3 text-gray-500">GST we collect for Govt</td></tr>
                                <tr class="border-t-2 border-navy-300 font-bold bg-navy-50"><td class="p-3">TOTAL</td><td class="p-3 text-right text-green-700">₹11,800</td><td class="p-3 text-right text-blue-700">₹11,800</td><td class="p-3 text-gray-600">✓ Balanced</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <h4 class="font-semibold text-blue-900 mb-2"><i class="fa fa-chart-line mr-2"></i>P&L Impact</h4>
                        <p class="text-sm text-blue-800"><strong>Revenue ↑ ₹10,000</strong> (taxable amount, excluding GST)</p>
                        <p class="text-xs text-blue-600 mt-1">GST collected is NOT revenue — it's a liability payable to govt.</p>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <h4 class="font-semibold text-green-900 mb-2"><i class="fa fa-balance-scale mr-2"></i>Balance Sheet Impact</h4>
                        <p class="text-sm text-green-800"><strong>Assets ↑ ₹11,800</strong> (AR)</p>
                        <p class="text-sm text-green-800"><strong>Liabilities ↑ ₹1,800</strong> (GST Payable)</p>
                        <p class="text-sm text-green-800"><strong>Equity ↑ ₹10,000</strong> (via retained earnings)</p>
                    </div>
                    <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
                        <h4 class="font-semibold text-orange-900 mb-2"><i class="fa fa-receipt mr-2"></i>When Payment Received</h4>
                        <p class="text-sm text-orange-800">DR Bank ₹11,800<br>CR Accounts Receivable ₹11,800</p>
                        <p class="text-xs text-orange-600 mt-1">AR zeroed. Bank balance increases.</p>
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Purchase Invoice ₹5,000 + 18% GST (Interstate → IGST)</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="bg-gray-100"><th class="text-left p-3">Account</th><th class="text-right p-3">Debit</th><th class="text-right p-3">Credit</th></tr></thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr><td class="p-3">Purchases (5000)</td><td class="p-3 text-right text-green-700 font-bold">₹5,000</td><td class="p-3 text-right">—</td></tr>
                                <tr><td class="p-3">IGST Input Credit (1230)</td><td class="p-3 text-right text-green-700 font-bold">₹900</td><td class="p-3 text-right">—</td></tr>
                                <tr><td class="p-3">Accounts Payable (2100)</td><td class="p-3 text-right">—</td><td class="p-3 text-right text-red-700 font-bold">₹5,900</td></tr>
                                <tr class="font-bold bg-gray-100"><td class="p-3">TOTAL</td><td class="p-3 text-right">₹5,900</td><td class="p-3 text-right">₹5,900</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-3 text-sm text-gray-600"><i class="fa fa-info-circle text-blue-500 mr-1"></i>IGST Input (₹900) can be offset against IGST/CGST/SGST Output liability in GSTR-3B.</p>
                </div>
            </div>
        </div>

        {{-- GST Flow --}}
        <div x-show="tab==='gst-flow'" x-cloak class="flow-section">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">GST Compliance Flow</h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h3 class="font-semibold text-navy-600 mb-4">Monthly Filing Cycle</h3>
                    @foreach([
                        ['', 'Create & post GST invoices (CGST/SGST or IGST auto-selected based on state)'],
                        ['', 'E-invoice: if turnover > threshold, generate IRN via GSP gateway'],
                        ['', 'E-way bill for goods movement > ₹50,000'],
                        ['', 'At month end: Generate GSTR-1 (outward supplies)'],
                        ['', 'Import GSTR-2B (inward supply ITC statement from GST portal)'],
                        ['', 'Run GSTR-2B Reconciliation: match purchase invoices vs 2B'],
                        ['', 'Resolve mismatches, then file GSTR-3B (net tax payable)'],
                        ['', 'Pay GST liability after adjusting ITC'],
                    ] as [$n, $step])
                    <div class="flex items-start space-x-3 mb-3">
                        <div class="w-2 h-2 rounded-full bg-navy-600 mt-2 flex-shrink-0"></div>
                        <p class="text-gray-700 text-sm">{{ $step }}</p>
                    </div>
                    @endforeach
                </div>
                <div>
                    <h3 class="font-semibold text-green-600 mb-4">GST Rate Engine Logic</h3>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between py-2 border-b border-gray-200">
                            <span class="text-gray-600">Supplier state = Buyer state?</span>
                            <span class="font-medium text-blue-600">CGST + SGST</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200">
                            <span class="text-gray-600">Supplier state ≠ Buyer state?</span>
                            <span class="font-medium text-purple-600">IGST only</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200">
                            <span class="text-gray-600">Export / SEZ supply?</span>
                            <span class="font-medium text-green-600">Zero-rated (0%)</span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-gray-600">Reverse Charge (RCM)?</span>
                            <span class="font-medium text-orange-600">Buyer pays GST</span>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">HSN/SAC code in each line item → rate engine auto-picks the applicable rate from the rate master.</p>
                </div>
            </div>
        </div>

        {{-- Bank Reconciliation Flow --}}
        <div x-show="tab==='bank-flow'" x-cloak class="flow-section">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Bank Passbook Reconciliation Flow</h2>
            <div class="space-y-4">
                @foreach([
                    ['1', 'Setup bank account', 'Add your bank in Banking → Bank Accounts. Link to a Ledger account (e.g. Current Account 1410).', 'blue'],
                    ['2', 'Download passbook', 'From your bank: download statement as CSV. Most banks (SBI, HDFC, ICICI, Kotak) offer CSV/XLS export.', 'gray'],
                    ['3', 'Upload CSV', 'Go to Banking → Import Passbook. Map columns: Date, Description, Debit, Credit, Balance. Set header rows to skip.', 'green'],
                    ['4', 'Auto-import', 'Lekhya reads each row and creates BankTransaction records. Duplicate rows (same date+amount+description) are skipped.', 'purple'],
                    ['5', 'Match transactions', 'Compare bank transactions against journal entries. AI suggests matches based on amount, date, and description similarity.', 'orange'],
                    ['6', 'Mark cleared', 'Click "Match" to link a bank row to a journal entry. Status changes to Reconciled.', 'teal'],
                    ['7', 'Resolve differences', 'Unmatched bank credits → create receipt voucher. Unmatched debits → create payment voucher. Bank charges → post to Bank Charges account.', 'red'],
                    ['8', 'Complete reconciliation', 'Enter closing balance from bank statement. Lekhya confirms book balance = statement balance. Lock the reconciliation.', 'green'],
                ] as [$n, $title, $desc, $color])
                <div class="flex space-x-4">
                    <div class="w-10 h-10 rounded-full bg-{{ $color }}-100 border-2 border-{{ $color }}-300 flex items-center justify-center text-{{ $color }}-700 font-bold flex-shrink-0">{{ $n }}</div>
                    <div class="flex-1 pb-4 border-b border-gray-100">
                        <p class="font-semibold text-gray-900">{{ $title }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-6 p-5 bg-blue-50 border border-blue-200 rounded-xl">
                <h4 class="font-semibold text-blue-900 mb-2"><i class="fa fa-lightbulb mr-2"></i>Supported CSV Formats</h4>
                <div class="grid sm:grid-cols-2 gap-2 text-sm text-blue-800">
                    @foreach(['SBI (State Bank of India)', 'HDFC Bank', 'ICICI Bank', 'Axis Bank', 'Kotak Mahindra', 'Punjab National Bank', 'Bank of Baroda', 'Any custom CSV with column mapping'] as $bank)
                    <div class="flex items-center space-x-2"><i class="fa fa-check text-green-500"></i><span>{{ $bank }}</span></div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- AI Flow --}}
        <div x-show="tab==='ai-flow'" x-cloak class="flow-section">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">AI-Assisted Accounting Flow</h2>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                <p class="text-amber-900 font-semibold"><i class="fa fa-shield-halved mr-2"></i>AI Rule: AI NEVER writes to the ledger. It only PROPOSES — a human must approve every AI suggestion.</p>
            </div>
            @foreach([
                ['Invoice Extraction', 'PDF/Image → AI reads → Draft invoice created → Accountant reviews & corrects → Posts to ledger', 'robot', 'blue'],
                ['Account Auto-Coding', 'AI looks at line item description → suggests ledger account → accountant approves or corrects → System learns from corrections', 'code', 'purple'],
                ['GSTR-2B Matching', 'AI fuzzy-matches purchase invoices against 2B data → highlights mismatches → accountant reconciles', 'arrows-rotate', 'green'],
                ['Bank Line Matching', 'AI matches bank transaction to journal entry by amount + date + description similarity → suggests match → human confirms', 'building-columns', 'orange'],
                ['NL Queries', 'Type: "What are total sales in April?" → AI generates safe SQL → returns table/number → user can export', 'comment', 'teal'],
                ['Anomaly Detection', 'AI flags duplicate invoices, out-of-pattern amounts, unusual vendor patterns → flagged for human review only', 'triangle-exclamation', 'red'],
            ] as [$title, $desc, $icon, $color])
            <div class="mb-4 p-4 bg-white border border-gray-100 rounded-xl shadow-sm flex space-x-4">
                <div class="w-10 h-10 rounded-lg bg-{{ $color }}-100 flex items-center justify-center flex-shrink-0">
                    <i class="fa fa-{{ $icon }} text-{{ $color }}-600"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ $title }}</p>
                    <p class="text-sm text-gray-600 mt-1">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pramaan Flow --}}
        <div x-show="tab==='pramaan-flow'" x-cloak class="flow-section">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Lekhya Pramaan — CA Edition Flow</h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h3 class="font-semibold text-purple-600 mb-4">Tax Audit (3CD) Workflow</h3>
                    @foreach([
                        ['Staff prepares draft 3CD from books', 'preparer'],
                        ['CA reviews — flags issues, requests corrections', 'review'],
                        ['Corrections made (revision cycle)', 'pencil'],
                        ['CA generates UDIN from ICAI portal', 'certificate'],
                        ['CA signs report with DSC', 'file-signature'],
                        ['Firm-branded PDF generated with letterhead', 'file-pdf'],
                        ['Working papers attached to audit vault', 'folder'],
                        ['Report delivered to client', 'envelope'],
                    ] as [$step, $icon])
                    <div class="flex items-center space-x-3 mb-3">
                        <i class="fa fa-{{ $icon }} text-purple-500 w-5"></i>
                        <p class="text-sm text-gray-700">{{ $step }}</p>
                    </div>
                    @endforeach
                </div>
                <div>
                    <h3 class="font-semibold text-blue-600 mb-4">Multi-Client Practice Dashboard</h3>
                    <div class="space-y-3">
                        @foreach([
                            ['Compliance Calendar', 'All clients\' GST/TDS/ROC/Audit due dates in one view. Color-coded: Green=done, Orange=due soon, Red=overdue.'],
                            ['Bulk GSTR-2B Recon', 'Run reconciliation for all connected clients in one click. Exceptions flagged per client.'],
                            ['Notice Tracker', 'Track GST/IT notices per client. Response due dates, status, document vault.'],
                            ['White-label Reports', 'Firm logo, letterhead, and digital signature on all reports. Client-facing PDF looks like your own firm output.'],
                        ] as [$title, $desc])
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="font-medium text-blue-900 text-sm">{{ $title }}</p>
                            <p class="text-xs text-blue-700 mt-1">{{ $desc }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Tally Migration --}}
        <div x-show="tab==='tally-flow'" x-cloak class="flow-section">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Tally ERP → Lekhya Migration Flow</h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <h3 class="font-semibold text-orange-600 mb-4">Step-by-Step Guide</h3>
                    @foreach([
                        ['1', 'Export from Tally', 'In Tally Prime: Gateway → Data → Export → XML format. Select "All Masters + All Vouchers". Choose the period you want to migrate.'],
                        ['2', 'Upload to Lekhya', 'Go to Accounting → Tally Migration. Upload the XML file. Lekhya parses and shows a preview: X ledgers, Y vouchers found.'],
                        ['3', 'Preview & review', 'Review the mapping table. Lekhya shows how Tally Groups map to Lekhya account types. Check for any flagged errors.'],
                        ['4', 'Run import', 'Click "Start Import". Lekhya imports all ledgers as accounts, parties as vendors/customers, and vouchers as journal entries.'],
                        ['5', 'Verify trial balance', 'After import, run Trial Balance. Compare with Tally\'s trial balance. They should match to the last rupee.'],
                        ['6', 'Post opening balances', 'Lekhya auto-creates opening balance journal from Tally\'s opening balances. Verify asset/liability totals.'],
                    ] as [$n, $title, $desc])
                    <div class="flex space-x-3">
                        <div class="w-7 h-7 rounded-full bg-orange-100 border-2 border-orange-300 flex items-center justify-center text-orange-700 font-bold text-sm flex-shrink-0 mt-0.5">{{ $n }}</div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ $title }}</p>
                            <p class="text-sm text-gray-600 mt-0.5">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 mb-4">What gets migrated</h3>
                    @foreach([
                        ['✅', 'Chart of accounts (all groups & ledgers)'],
                        ['✅', 'Customers & vendors (Sundry Debtors/Creditors)'],
                        ['✅', 'All vouchers (Sales, Purchase, Receipt, Payment, Journal, Contra)'],
                        ['✅', 'Opening balances for all ledgers'],
                        ['✅', 'GSTIN for parties (if present in Tally)'],
                        ['⚠️', 'Tally payroll data (not supported — export separately)'],
                        ['⚠️', 'Fixed asset depreciation schedules (manual review required)'],
                        ['❌', 'Tally ERP customizations / TDL scripts (not applicable)'],
                    ] as [$status, $item])
                    <div class="flex items-center space-x-3 py-2 border-b border-gray-100">
                        <span class="text-lg">{{ $status }}</span>
                        <span class="text-sm text-gray-700">{{ $item }}</span>
                    </div>
                    @endforeach

                    <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                        <h4 class="font-semibold text-green-900 mb-2">Tally XML Export Quick Guide</h4>
                        <ol class="text-sm text-green-800 space-y-1 list-decimal list-inside">
                            <li>Open Tally Prime → Gateway of Tally</li>
                            <li>Press F1 → Help → TallyPrime Data Exchange</li>
                            <li>Or: Data → Export → Choose XML format</li>
                            <li>Select company, period, and "All" for data</li>
                            <li>Save as .xml file → Upload to Lekhya</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>[x-cloak] { display: none; }</style>
@endsection
