<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Posts a validated invoice as a double-entry journal.
 * After posting, the invoice status becomes 'posted' and journal is immutable.
 */
class InvoicePostingService
{
    public function __construct(private JournalEngine $engine) {}

    public function post(Invoice $invoice, int $userId): Invoice
    {
        if ($invoice->isLocked()) {
            throw new \DomainException("Invoice {$invoice->invoice_number} is already posted/locked.");
        }

        $tenant = $invoice->tenant;
        $fiscalYear = FiscalYear::where('tenant_id', $tenant->id)->where('is_current', true)->firstOrFail();
        $lines = $invoice->lines()->with('account')->get();

        // Resolve system accounts
        $systemAccounts = $this->resolveSystemAccounts($tenant->id, $invoice);

        $journalLines = [];
        $isSales = in_array($invoice->type, ['sales', 'purchase_return']);

        if ($isSales) {
            // DEBIT: Accounts Receivable
            $journalLines[] = [
                'account_id' => $systemAccounts['receivable'],
                'debit'      => $invoice->total_amount,
                'credit'     => 0,
                'narration'  => "Invoice {$invoice->invoice_number} - {$invoice->party->name}",
            ];

            // CREDIT: Each sales line item account
            foreach ($lines as $line) {
                $accountId = $line->account_id ?? $systemAccounts['sales'];
                $journalLines[] = [
                    'account_id' => $accountId,
                    'debit'      => 0,
                    'credit'     => $line->taxable_amount,
                    'narration'  => $line->description,
                ];
            }

            // CREDIT: GST Output accounts
            if ($invoice->cgst_amount > 0) {
                $journalLines[] = ['account_id' => $systemAccounts['cgst_output'], 'debit' => 0, 'credit' => $invoice->cgst_amount, 'narration' => 'CGST Output'];
                $journalLines[] = ['account_id' => $systemAccounts['sgst_output'], 'debit' => 0, 'credit' => $invoice->sgst_amount, 'narration' => 'SGST Output'];
            }
            if ($invoice->igst_amount > 0) {
                $journalLines[] = ['account_id' => $systemAccounts['igst_output'], 'debit' => 0, 'credit' => $invoice->igst_amount, 'narration' => 'IGST Output'];
            }
        } else {
            // Purchase invoice
            // CREDIT: Accounts Payable
            $journalLines[] = [
                'account_id' => $systemAccounts['payable'],
                'debit'      => 0,
                'credit'     => $invoice->total_amount,
                'narration'  => "Invoice {$invoice->invoice_number} - {$invoice->party->name}",
            ];

            foreach ($lines as $line) {
                $accountId = $line->account_id ?? $systemAccounts['purchase'];
                $journalLines[] = [
                    'account_id' => $accountId,
                    'debit'      => $line->taxable_amount,
                    'credit'     => 0,
                    'narration'  => $line->description,
                ];
            }

            // GST Input accounts
            if ($invoice->cgst_amount > 0) {
                $journalLines[] = ['account_id' => $systemAccounts['cgst_input'], 'debit' => $invoice->cgst_amount, 'credit' => 0, 'narration' => 'CGST Input'];
                $journalLines[] = ['account_id' => $systemAccounts['sgst_input'], 'debit' => $invoice->sgst_amount, 'credit' => 0, 'narration' => 'SGST Input'];
            }
            if ($invoice->igst_amount > 0) {
                $journalLines[] = ['account_id' => $systemAccounts['igst_input'], 'debit' => $invoice->igst_amount, 'credit' => 0, 'narration' => 'IGST Input'];
            }
        }

        // Round off
        if (abs($invoice->round_off) > 0.0001) {
            $isDebit = $invoice->round_off > 0;
            $journalLines[] = [
                'account_id' => $systemAccounts['round_off'],
                'debit'      => $isDebit ? $invoice->round_off : 0,
                'credit'     => $isDebit ? 0 : abs($invoice->round_off),
                'narration'  => 'Round off',
            ];
        }

        return DB::transaction(function () use ($invoice, $journalLines, $fiscalYear, $userId) {
            $journal = $this->engine->post([
                'tenant_id'      => $invoice->tenant_id,
                'fiscal_year_id' => $fiscalYear->id,
                'voucher_type'   => $invoice->type === 'sales' ? 'sales' : 'purchase',
                'date'           => $invoice->invoice_date->format('Y-m-d'),
                'narration'      => "Auto-posted: Invoice {$invoice->invoice_number}",
                'reference'      => $invoice->invoice_number,
                'lines'          => $journalLines,
                'created_by'     => $userId,
            ]);

            $invoice->update([
                'status'     => 'posted',
                'journal_id' => $journal->id,
                'posted_at'  => now(),
            ]);

            return $invoice->fresh();
        });
    }

    private function resolveSystemAccounts(int $tenantId, Invoice $invoice): array
    {
        $get = fn(string $code) => Account::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('id') ?? throw new \DomainException("System account '{$code}' not found. Please seed chart of accounts.");

        return [
            'receivable'  => $invoice->party->receivable_account_id ?? $get('1100'),
            'payable'     => $invoice->party->payable_account_id ?? $get('2100'),
            'sales'       => $get('4000'),
            'purchase'    => $get('5000'),
            'cgst_output' => $get('2210'),
            'sgst_output' => $get('2220'),
            'igst_output' => $get('2230'),
            'cgst_input'  => $get('1210'),
            'sgst_input'  => $get('1220'),
            'igst_input'  => $get('1230'),
            'round_off'   => $get('8000'),
        ];
    }
}
