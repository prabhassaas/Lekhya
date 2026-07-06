@extends('layouts.marketing')
@section('title', 'Bank Reconciliation Guide — Lekhya Help')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <nav class="text-sm text-gray-500 mb-8">
        <a href="{{ route('marketing.help') }}" class="hover:text-navy-600">Help</a> → <span class="text-gray-900">Bank Reconciliation</span>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 mb-4">Bank Reconciliation</h1>
    <p class="text-lg text-gray-600 mb-10">Match your bank statement with your books in minutes — Lekhya does the heavy lifting.</p>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-10">
        <h3 class="font-semibold text-blue-900 mb-1"><i class="fa fa-circle-info mr-2"></i>What is bank reconciliation?</h3>
        <p class="text-sm text-blue-800">It's the process of confirming that money in your bank account matches what's recorded in your accounting books. Lekhya compares your uploaded bank statement against your journal entries and highlights any differences.</p>
    </div>

    <div class="space-y-12">

        {{-- Step 1 --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4"><span class="text-navy-600">01.</span> Download Your Bank Statement</h2>
            <p class="text-gray-600 mb-4">Log in to your bank's internet banking and download a statement for the period you want to reconcile. Lekhya accepts:</p>
            <div class="grid sm:grid-cols-3 gap-4">
                @foreach([
                    ['CSV', 'Most banks offer CSV export. This is the easiest format.', 'file-csv', 'green'],
                    ['Excel (.xlsx)', 'HDFC, ICICI, Axis all support Excel export.', 'file-excel', 'blue'],
                    ['PDF (text-based)', 'Lekhya can extract from selectable-text PDFs. Scanned PDFs are not supported.', 'file-pdf', 'red'],
                ] as [$fmt, $desc, $icon, $color])
                <div class="bg-{{ $color }}-50 border border-{{ $color }}-200 rounded-xl p-4">
                    <i class="fa fa-{{ $icon }} text-{{ $color }}-600 text-xl mb-2 block"></i>
                    <p class="font-semibold text-{{ $color }}-900 text-sm">{{ $fmt }}</p>
                    <p class="text-xs text-{{ $color }}-800 mt-1">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
            <div class="mt-4 bg-amber-50 border border-amber-200 rounded-xl p-4">
                <p class="text-sm text-amber-800"><i class="fa fa-lightbulb mr-1 text-amber-600"></i><strong>Tip:</strong> Download one month at a time for your first reconciliation. Once you're comfortable, you can do quarterly batches.</p>
            </div>
        </section>

        {{-- Step 2 --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4"><span class="text-navy-600">02.</span> Upload to Lekhya</h2>
            <div class="space-y-4">
                <p class="text-gray-600">Go to <strong>Banking → Reconciliation → Import Passbook</strong>.</p>
                <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100">
                    @foreach([
                        ['Select Bank Account', 'Choose which bank account this statement belongs to (create it first under Accounting → Accounts → Bank Accounts if needed).'],
                        ['Upload File', 'Drag and drop your CSV/Excel file, or click to browse.'],
                        ['Map Columns', 'Tell Lekhya which column is the date, which is debit, which is credit, and which is the narration/description. Lekhya remembers your mapping for next time.'],
                        ['Preview & Confirm', 'Review the transactions before importing. Lekhya flags any rows it could not parse.'],
                    ] as [$step, $desc])
                    <div class="flex gap-4 px-5 py-4">
                        <div class="w-2 h-2 rounded-full bg-navy-600 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="font-semibold text-gray-900 text-sm">{{ $step }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Step 3 --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4"><span class="text-navy-600">03.</span> Match Transactions</h2>
            <p class="text-gray-600 mb-4">Lekhya automatically matches bank rows to your journal entries based on date and amount. You review the matches and fix anything it got wrong.</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                    <h4 class="font-semibold text-green-900 mb-2"><i class="fa fa-circle-check mr-1"></i> Auto-matched</h4>
                    <p class="text-sm text-green-800">Date and amount match an existing journal entry. Just confirm and move on.</p>
                </div>
                <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
                    <h4 class="font-semibold text-orange-900 mb-2"><i class="fa fa-triangle-exclamation mr-1"></i> Needs attention</h4>
                    <p class="text-sm text-orange-800">No matching entry found. Either the entry is missing in your books, or the date/amount differs. You can create a journal entry directly from this screen.</p>
                </div>
            </div>
        </section>

        {{-- Step 4 --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4"><span class="text-navy-600">04.</span> Handle Unmatched Items</h2>
            <p class="text-gray-600 mb-4">For each unmatched bank transaction, you have three options:</p>
            <div class="space-y-3">
                @foreach([
                    ['Create a journal entry', 'Something happened in the bank that isn\'t recorded yet (bank charges, interest, a refund). Click "Create Entry" and fill in the account.', 'plus', 'navy'],
                    ['Link to an existing entry', 'The entry exists but the date or amount is slightly different (rounding, bank delay). Search and link manually.', 'link', 'blue'],
                    ['Mark as not in books', 'Outstanding cheques, deposits in transit — items you know about but haven\'t cleared yet. These carry forward to next month\'s reconciliation.', 'clock', 'amber'],
                ] as [$action, $desc, $icon, $color])
                <div class="flex gap-4 bg-white border border-gray-200 rounded-xl p-4">
                    <div class="w-10 h-10 bg-{{ $color === 'navy' ? 'navy-50' : $color.'-50' }} rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-{{ $icon }} text-{{ $color === 'navy' ? 'navy-600' : $color.'-600' }}"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $action }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        {{-- Step 5 --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4"><span class="text-navy-600">05.</span> Complete the Period</h2>
            <p class="text-gray-600 mb-4">Once all transactions are matched or marked, click <strong>Complete Reconciliation</strong>. Lekhya will:</p>
            <ul class="space-y-2">
                @foreach([
                    'Lock this period so no accidental changes happen',
                    'Show you the reconciliation summary: opening balance, closing balance, any difference',
                    'Generate a reconciliation statement you can save as PDF for your auditor',
                    'Carry forward any outstanding items to the next period automatically',
                ] as $item)
                <li class="flex gap-2 text-sm text-gray-600">
                    <i class="fa fa-circle-check text-green-500 mt-0.5 flex-shrink-0"></i>
                    <span>{{ $item }}</span>
                </li>
                @endforeach
            </ul>
        </section>

        {{-- Common issues --}}
        <section class="bg-gray-50 border border-gray-200 rounded-2xl p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Common Issues & Fixes</h2>
            <div class="space-y-3">
                @foreach([
                    ['My bank statement has debit and credit in one column with +/- signs', 'During column mapping, choose "Single Amount Column" and Lekhya will split it automatically.'],
                    ['Difference doesn\'t go to zero', 'Check for bank charges, GST on bank charges, or interest that isn\'t recorded yet. Also check for duplicate entries in your books.'],
                    ['Dates don\'t match by 1–2 days', 'Bank statements often use value date, not transaction date. Use "Flexible date matching" (±3 days) in settings.'],
                    ['My bank format isn\'t recognized', 'Contact support with a sample 5-row CSV. We add new bank formats regularly.'],
                ] as [$q, $a])
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <p class="font-semibold text-gray-800 text-sm mb-1">{{ $q }}</p>
                    <p class="text-sm text-gray-500">{{ $a }}</p>
                </div>
                @endforeach
            </div>
        </section>

    </div>
</div>
@endsection
