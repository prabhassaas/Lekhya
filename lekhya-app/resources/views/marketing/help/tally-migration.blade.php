@extends('layouts.marketing')
@section('title', 'Tally ERP Migration Guide — Lekhya')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <nav class="text-sm text-gray-500 mb-8">
        <a href="{{ route('marketing.help') }}" class="hover:text-navy-600">Help</a> → <span class="text-gray-900">Tally Migration</span>
    </nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-4">Migrate from Tally ERP to Lekhya</h1>
    <p class="text-lg text-gray-600 mb-8">Move all your ledgers, parties, and vouchers from Tally ERP 9 or Tally Prime to Lekhya in 3 steps. No data loss. No manual re-entry.</p>

    <div class="bg-green-50 border border-green-200 rounded-xl p-5 mb-8">
        <h3 class="font-semibold text-green-900 mb-2"><i class="fa fa-check-circle mr-2"></i>What you need</h3>
        <ul class="text-sm text-green-800 space-y-1">
            <li>• Tally ERP 9 (v9.2+) or Tally Prime (any version)</li>
            <li>• Export your company data as XML (see step 1)</li>
            <li>• A Lekhya account (14-day free trial, no card needed)</li>
        </ul>
    </div>

    <div class="space-y-8">
        <div class="border border-gray-200 rounded-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4"><span class="text-navy-600">Step 1.</span> Export from Tally</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-gray-800 mb-3">Tally Prime</h3>
                    <ol class="text-sm text-gray-700 space-y-2 list-decimal list-inside">
                        <li>Open your company in Tally Prime</li>
                        <li>Go to <strong>Import & Export → Export</strong></li>
                        <li>Select <strong>Masters</strong> → Format: XML → Export all ledgers</li>
                        <li>Repeat for <strong>Vouchers</strong> → select date range</li>
                        <li>Save both XML files</li>
                    </ol>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 mb-3">Tally ERP 9</h3>
                    <ol class="text-sm text-gray-700 space-y-2 list-decimal list-inside">
                        <li>Gateway of Tally → <strong>Data</strong></li>
                        <li>Select <strong>Export → XML format</strong></li>
                        <li>Choose <strong>All Masters + Vouchers</strong></li>
                        <li>Select fiscal year period</li>
                        <li>Export and save the .xml file</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="border border-gray-200 rounded-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4"><span class="text-navy-600">Step 2.</span> Upload to Lekhya</h2>
            <ol class="text-sm text-gray-700 space-y-2 list-decimal list-inside">
                <li>Log in to Lekhya → Accounting → <strong>Tally Migration</strong></li>
                <li>Click <strong>Upload XML</strong> and select your Tally export file</li>
                <li>Lekhya parses it and shows a preview: number of ledgers, vouchers found, and any errors</li>
                <li>Review the mapping (Tally groups → Lekhya account types)</li>
                <li>Click <strong>Start Import</strong></li>
            </ol>
        </div>

        <div class="border border-gray-200 rounded-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4"><span class="text-navy-600">Step 3.</span> Verify in Lekhya</h2>
            <ol class="text-sm text-gray-700 space-y-2 list-decimal list-inside">
                <li>Go to Reports → <strong>Trial Balance</strong></li>
                <li>Compare with Tally's Trial Balance (same period)</li>
                <li>Totals should match to the last rupee</li>
                <li>Check Chart of Accounts — all your Tally ledgers should appear</li>
                <li>Check Parties — customers and vendors from Sundry Debtors/Creditors are imported</li>
            </ol>
            <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-sm text-amber-800"><strong>If totals don't match:</strong> Check the import log for failed vouchers. Common issues: vouchers with only one line (Tally occasionally creates unbalanced entries), unsupported voucher types. These are listed in the error report — you can fix them manually in Lekhya.</p>
            </div>
        </div>
    </div>

    <div class="mt-12 bg-navy-50 border border-navy-200 rounded-xl p-6">
        <h3 class="font-semibold text-navy-900 mb-3">Need help with migration?</h3>
        <p class="text-sm text-navy-800">If you have a complex Tally setup (multiple companies, payroll, custom voucher types), contact us. We offer a free migration assistance call for Lekhya Practice and Firm plan users.</p>
        <a href="{{ route('marketing.contact') }}" class="mt-3 inline-block bg-navy-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-navy-700 transition">Get Migration Help →</a>
    </div>
</div>
@endsection
