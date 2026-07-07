@extends('layouts.marketing')
@section('title', 'Double-Entry Accounting Explained — Lekhya Help')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <nav class="text-sm text-gray-500 mb-8">
        <a href="{{ route('marketing.help') }}" class="hover:text-navy-600">Help</a> → <span class="text-gray-900">Double-Entry Basics</span>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 mb-4">Double-Entry Accounting — Plain English</h1>
    <p class="text-lg text-gray-600 mb-10">You don't need to be a CA to understand this. Every rupee tells two stories — Lekhya tracks both.</p>

    <div class="space-y-12">

        {{-- The core idea --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">The Core Idea</h2>
            <div class="bg-navy-600 rounded-2xl p-6 text-white mb-6">
                <p class="text-lg font-medium text-center">Every transaction affects at least <span class="underline">two</span> accounts.</p>
                <p class="text-sm text-center text-blue-200 mt-2">Money always comes from somewhere and goes somewhere else.</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <p class="font-semibold text-gray-800 mb-3">Example: You sell goods worth ₹10,000 to a customer</p>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <p class="text-xs font-bold text-green-700 uppercase tracking-wider mb-2">Debit (Money coming in or asset increasing)</p>
                        <p class="text-lg font-bold text-green-900">Accounts Receivable ₹10,000</p>
                        <p class="text-sm text-green-700 mt-1">Customer now owes you ₹10,000</p>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <p class="text-xs font-bold text-blue-700 uppercase tracking-wider mb-2">Credit (Revenue being recognized)</p>
                        <p class="text-lg font-bold text-blue-900">Sales Revenue ₹10,000</p>
                        <p class="text-sm text-blue-700 mt-1">You earned ₹10,000 in revenue</p>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-4 text-center">Debit = Credit ✓ (₹10,000 = ₹10,000) — the equation is always balanced</p>
            </div>
        </section>

        {{-- Debits and Credits --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Debits & Credits — The Simple Rule</h2>
            <p class="text-gray-600 mb-4">Forget the confusing textbook definitions. Here's how to think about it in practice:</p>
            <div class="overflow-x-auto">
                <table class="w-full border border-gray-200 rounded-xl overflow-hidden text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Account Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-green-700">Debit does...</th>
                            <th class="px-4 py-3 text-left font-semibold text-blue-700">Credit does...</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach([
                            ['Assets (cash, receivables, equipment)', 'Increases ↑', 'Decreases ↓'],
                            ['Liabilities (loans, payables)', 'Decreases ↓', 'Increases ↑'],
                            ['Capital / Equity (owner\'s investment)', 'Decreases ↓', 'Increases ↑'],
                            ['Revenue / Income (sales, interest)', 'Decreases ↓', 'Increases ↑'],
                            ['Expenses (rent, salary, purchases)', 'Increases ↑', 'Decreases ↓'],
                        ] as [$type, $dr, $cr])
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ $type }}</td>
                            <td class="px-4 py-3 font-medium text-green-700">{{ $dr }}</td>
                            <td class="px-4 py-3 font-medium text-blue-700">{{ $cr }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- 3 real-world examples --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">3 Real-World Examples</h2>
            <div class="space-y-4">
                @foreach([
                    ['You pay ₹5,000 rent by bank transfer',
                        ['Rent Expense', '5,000', 'debit'],
                        ['Bank Account', '5,000', 'credit'],
                        'Rent expense goes up. Your bank balance goes down.'],
                    ['You buy goods worth ₹20,000 on credit from a supplier',
                        ['Stock / Purchases', '20,000', 'debit'],
                        ['Accounts Payable (Supplier)', '20,000', 'credit'],
                        'Your stock goes up. You now owe the supplier.'],
                    ['You receive ₹10,000 cash from the customer who owed you',
                        ['Cash / Bank', '10,000', 'debit'],
                        ['Accounts Receivable', '10,000', 'credit'],
                        'Cash goes up. The customer\'s debt is cleared.'],
                ] as [$scenario, $dr, $cr, $plain])
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <p class="font-semibold text-gray-900 mb-3">{{ $scenario }}</p>
                    <div class="grid sm:grid-cols-2 gap-3 mb-3">
                        <div class="bg-green-50 rounded-lg p-3 text-sm">
                            <span class="text-green-700 font-bold">DR</span> {{ $dr[0] }} <span class="float-right font-mono">₹{{ $dr[1] }}</span>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-3 text-sm">
                            <span class="text-blue-700 font-bold">CR</span> {{ $cr[0] }} <span class="float-right font-mono">₹{{ $cr[1] }}</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">{{ $plain }}</p>
                </div>
                @endforeach
            </div>
        </section>

        {{-- How Lekhya handles this --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">How Lekhya Handles This For You</h2>
            <p class="text-gray-600 mb-4">You don't have to think about debits and credits when creating invoices. Lekhya's journal engine does it automatically:</p>
            <div class="space-y-3">
                @foreach([
                    ['When you post an invoice', 'Lekhya debits Accounts Receivable and credits Sales Revenue + GST payable accounts automatically.', 'file-invoice'],
                    ['When you record a payment received', 'Lekhya debits Bank and credits Accounts Receivable — the receivable is cleared.', 'money-bill'],
                    ['When you record a purchase', 'Lekhya debits Purchases/Stock and credits Accounts Payable.', 'cart-shopping'],
                    ['Corrections are always via reversal', 'Lekhya never edits a posted entry. To fix a mistake, it creates a reversing entry and a corrected new one — just like a CA would.', 'rotate-left'],
                ] as [$when, $what, $icon])
                <div class="flex gap-4 bg-white border border-gray-200 rounded-xl p-4">
                    <div class="w-10 h-10 bg-navy-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-{{ $icon }} text-navy-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $when }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $what }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        {{-- P&L vs Balance Sheet --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">P&L vs Balance Sheet — What's the Difference?</h2>
            <div class="grid sm:grid-cols-2 gap-6">
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-bold text-gray-900 mb-2"><i class="fa fa-chart-line text-navy-600 mr-2"></i>Profit & Loss (P&L)</h3>
                    <p class="text-sm text-gray-600 mb-3">Shows <strong>income and expenses over a period</strong> (e.g. this financial year). Tells you if you made a profit or loss.</p>
                    <ul class="space-y-1 text-xs text-gray-500">
                        <li>→ Sales, Service Revenue</li>
                        <li>→ Cost of Goods Sold</li>
                        <li>→ Operating Expenses (rent, salary, etc.)</li>
                        <li>→ GST, taxes</li>
                        <li class="font-semibold text-gray-700 pt-1">= Net Profit or Loss</li>
                    </ul>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-bold text-gray-900 mb-2"><i class="fa fa-scale-balanced text-navy-600 mr-2"></i>Balance Sheet</h3>
                    <p class="text-sm text-gray-600 mb-3">Shows <strong>what you own and owe at a point in time</strong>. Always balanced: Assets = Liabilities + Capital.</p>
                    <ul class="space-y-1 text-xs text-gray-500">
                        <li>→ Assets: Cash, Bank, Receivables, Stock, Equipment</li>
                        <li>→ Liabilities: Loans, Payables, GST payable</li>
                        <li>→ Capital: Owner's equity + retained profits</li>
                        <li class="font-semibold text-gray-700 pt-1">= Always balanced</li>
                    </ul>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-4">In Lekhya: go to <strong>Accounting → Reports → Profit & Loss</strong> or <strong>Balance Sheet</strong> to see these for any date range.</p>
        </section>

    </div>
</div>
@endsection
