<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Settles invoices with a receipt (money in) or payment (money out): posts the
 * bank/cash double-entry voucher through the JournalEngine and clears the
 * invoices' balance_amount. This is the settlement half of the ledger — it is
 * what makes AR/AP ageing actually reduce.
 *
 * TDS: for a receipt the customer may withhold TDS (Dr TDS Receivable); for a
 * payment we may deduct TDS (Cr TDS Payable). The invoice is settled by the
 * GROSS allocation; the bank moves GROSS − TDS.
 */
class ReceiptPaymentService
{
    public function __construct(private JournalEngine $engine) {}

    public function record(array $data): Payment
    {
        $tenantId  = (int) $data['tenant_id'];
        $type      = ($data['type'] ?? 'receipt') === 'payment' ? 'payment' : 'receipt';
        $isReceipt = $type === 'receipt';
        $invType   = $isReceipt ? 'sales' : 'purchase';

        $allocations = collect($data['allocations'] ?? [])
            ->map(fn ($a) => ['invoice_id' => (int) ($a['invoice_id'] ?? 0), 'amount' => round((float) ($a['amount'] ?? 0), 2)])
            ->filter(fn ($a) => $a['invoice_id'] > 0 && $a['amount'] > 0)
            ->values();

        if ($allocations->isEmpty()) {
            throw ValidationException::withMessages(['allocations' => 'Select at least one bill and an amount to settle.']);
        }

        $invoices = Invoice::where('tenant_id', $tenantId)
            ->where('party_id', $data['party_id'])
            ->where('type', $invType)
            ->whereIn('id', $allocations->pluck('invoice_id'))
            ->whereIn('status', ['posted', 'partially_paid'])
            ->with('party')
            ->get()->keyBy('id');

        foreach ($allocations as $a) {
            $inv = $invoices->get($a['invoice_id']);
            if (! $inv) {
                throw ValidationException::withMessages(['allocations' => 'A selected bill is not open for settlement.']);
            }
            if ($a['amount'] > round((float) $inv->balance_amount, 2) + 0.001) {
                throw ValidationException::withMessages(['allocations' => "Amount for {$inv->invoice_number} exceeds its balance of ₹" . number_format((float) $inv->balance_amount, 2) . '.']);
            }
        }

        $gross = round($allocations->sum('amount'), 2);
        $tds   = round((float) ($data['tds_amount'] ?? 0), 2);
        $cash  = round($gross - $tds, 2);
        if ($tds < 0 || $cash < 0) {
            throw ValidationException::withMessages(['tds_amount' => 'TDS cannot exceed the settled amount.']);
        }

        $ledger = Account::where('tenant_id', $tenantId)->where('is_ledger', true)->findOrFail($data['ledger_account_id']);
        $fy     = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->firstOrFail();
        $get    = fn (string $code) => Account::where('tenant_id', $tenantId)->where('code', $code)->value('id')
            ?? throw new \DomainException("System account {$code} missing — seed the chart of accounts.");

        $party     = $invoices->first()->party;
        $partyName = $party->name ?? 'Party';
        $ref       = trim((string) ($data['reference'] ?? ''));

        // Build the balanced double entry.
        $lines = [];
        if ($isReceipt) {
            $recv = $party->receivable_account_id ?? $get('1100');
            $lines[] = ['account_id' => $ledger->id, 'debit' => $cash, 'credit' => 0, 'narration' => "Received from {$partyName}"];
            if ($tds > 0) {
                $lines[] = ['account_id' => $get('1700'), 'debit' => $tds, 'credit' => 0, 'narration' => 'TDS deducted by customer'];
            }
            $lines[] = ['account_id' => $recv, 'debit' => 0, 'credit' => $gross, 'narration' => "Settlement — {$partyName}"];
            $voucher = 'receipt';
        } else {
            $pay = $party->payable_account_id ?? $get('2100');
            $lines[] = ['account_id' => $pay, 'debit' => $gross, 'credit' => 0, 'narration' => "Paid to {$partyName}"];
            $lines[] = ['account_id' => $ledger->id, 'debit' => 0, 'credit' => $cash, 'narration' => "Payment — {$partyName}"];
            if ($tds > 0) {
                $lines[] = ['account_id' => $get('2300'), 'debit' => 0, 'credit' => $tds, 'narration' => 'TDS deducted'];
            }
            $voucher = 'payment';
        }

        $payment = DB::transaction(function () use ($data, $tenantId, $type, $fy, $lines, $voucher, $allocations, $invoices, $gross, $tds, $ledger, $partyName, $ref) {
            $journal = $this->engine->post([
                'tenant_id'      => $tenantId,
                'fiscal_year_id' => $fy->id,
                'voucher_type'   => $voucher,
                'date'           => $data['payment_date'],
                'narration'      => ($type === 'receipt' ? 'Receipt from ' : 'Payment to ') . $partyName . ($ref !== '' ? " (ref {$ref})" : ''),
                'reference'      => $ref !== '' ? $ref : null,
                'lines'          => $lines,
                'created_by'     => $data['created_by'],
            ]);

            $payment = Payment::create([
                'tenant_id'             => $tenantId,
                'fiscal_year_id'        => $fy->id,
                'type'                  => $type,
                // Globally unique (the scaffolded column carries a global unique):
                // voucher number is unique per tenant, journal id makes it global.
                'reference_number'      => $journal->voucher_number . '-' . $journal->id,
                'party_id'              => $data['party_id'],
                'account_id'            => $ledger->id,
                'date'                  => $data['payment_date'],
                'amount'                => $gross,
                'tds_amount'            => $tds,
                'payment_mode'          => ($data['mode'] ?? null) ?: 'bank_transfer', // column is NOT NULL

                'transaction_reference' => $ref !== '' ? $ref : null,
                'narration'             => $data['notes'] ?? null,
                'journal_id'            => $journal->id,
                'created_by'            => $data['created_by'],
            ]);

            foreach ($allocations as $a) {
                PaymentAllocation::create([
                    'tenant_id'  => $tenantId,
                    'payment_id' => $payment->id,
                    'invoice_id' => $a['invoice_id'],
                    'amount'     => $a['amount'],
                ]);

                $inv     = $invoices->get($a['invoice_id']);
                $paid    = round((float) $inv->paid_amount + $a['amount'], 2);
                $balance = round((float) $inv->total_amount - $paid, 2);
                $inv->update([
                    'paid_amount'    => $paid,
                    'balance_amount' => max(0, $balance),
                    'status'         => $balance <= 0.001 ? 'paid' : 'partially_paid',
                ]);
            }

            return $payment;
        });

        // Tell the rest of the team money moved (never blocks the settlement).
        app(\App\Services\Notification\Notifier::class)->toTenant(
            $tenantId,
            $type === 'receipt' ? 'Receipt recorded' : 'Payment recorded',
            '₹' . number_format($gross, 2) . ($type === 'receipt' ? ' received from ' : ' paid to ') . $partyName,
            route('accounting.payments.show', $payment),
            $type === 'receipt' ? 'fa-arrow-down' : 'fa-arrow-up',
            $type === 'receipt' ? 'green' : 'orange',
            $data['created_by'] ?? null,
        );

        return $payment;
    }
}
