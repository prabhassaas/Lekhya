<?php

namespace App\Services\Accounting;

use App\Models\FiscalYear;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Double-entry journal engine.
 * RULE: posted journals are immutable. Corrections = reversing entries only.
 * RULE: total debit must equal total credit before posting.
 */
class JournalEngine
{
    /**
     * Create and post a journal entry.
     *
     * @param  array{
     *   tenant_id: int,
     *   fiscal_year_id: int,
     *   voucher_type: string,
     *   date: string,
     *   narration: string,
     *   reference?: string,
     *   lines: array<array{account_id: int, debit: float, credit: float, narration?: string}>,
     *   created_by: int,
     * } $data
     */
    public function post(array $data): Journal
    {
        $this->assertBalanced($data['lines']);

        return DB::transaction(function () use ($data) {
            $voucherNumber = $this->generateVoucherNumber(
                $data['tenant_id'],
                $data['voucher_type'],
                $data['date']
            );

            $totalDebit = collect($data['lines'])->sum('debit');
            $totalCredit = collect($data['lines'])->sum('credit');

            $journal = Journal::create([
                'tenant_id'      => $data['tenant_id'],
                'fiscal_year_id' => $data['fiscal_year_id'],
                'voucher_number' => $voucherNumber,
                'voucher_type'   => $data['voucher_type'],
                'date'           => $data['date'],
                'narration'      => $data['narration'] ?? null,
                'reference'      => $data['reference'] ?? null,
                'total_debit'    => $totalDebit,
                'total_credit'   => $totalCredit,
                'is_posted'      => true,
                'posted_at'      => now(),
                'created_by'     => $data['created_by'],
            ]);

            foreach ($data['lines'] as $order => $line) {
                JournalLine::create([
                    'tenant_id'  => $data['tenant_id'],
                    'journal_id' => $journal->id,
                    'account_id' => $line['account_id'],
                    'line_order' => $order,
                    'debit'      => $line['debit'] ?? 0,
                    'credit'     => $line['credit'] ?? 0,
                    'narration'  => $line['narration'] ?? null,
                ]);
            }

            return $journal->fresh(['lines']);
        });
    }

    /**
     * Reverse a posted journal. Returns the new reversing journal.
     * The original journal is marked as reversed — never deleted or modified.
     */
    public function reverse(Journal $journal, string $date, int $userId, string $reason = ''): Journal
    {
        if (! $journal->is_posted) {
            throw new \DomainException('Cannot reverse an unposted journal.');
        }

        if ($journal->is_reversed) {
            throw new \DomainException('Journal has already been reversed.');
        }

        return DB::transaction(function () use ($journal, $date, $userId, $reason) {
            $reversedLines = $journal->lines->map(fn($line) => [
                'account_id' => $line->account_id,
                'debit'      => $line->credit, // swap debit <-> credit
                'credit'     => $line->debit,
                'narration'  => 'Reversal: ' . ($line->narration ?? ''),
            ])->all();

            $reversing = $this->post([
                'tenant_id'      => $journal->tenant_id,
                'fiscal_year_id' => $journal->fiscal_year_id,
                'voucher_type'   => 'reversal',
                'date'           => $date,
                'narration'      => "Reversal of {$journal->voucher_number}. " . $reason,
                'reference'      => $journal->voucher_number,
                'lines'          => $reversedLines,
                'created_by'     => $userId,
            ]);

            // Mark original as reversed — this is the immutability guarantee
            $journal->update([
                'is_reversed'           => true,
                'reversed_by_journal_id' => $reversing->id,
            ]);
            $reversing->update(['reversal_of_journal_id' => $journal->id]);

            return $reversing;
        });
    }

    private function assertBalanced(array $lines): void
    {
        $debit  = array_sum(array_column($lines, 'debit'));
        $credit = array_sum(array_column($lines, 'credit'));

        if (abs($debit - $credit) > 0.0001) {
            throw ValidationException::withMessages([
                'lines' => "Journal is not balanced: debit {$debit} ≠ credit {$credit}",
            ]);
        }

        if ($debit <= 0) {
            throw ValidationException::withMessages([
                'lines' => 'Journal must have at least one non-zero entry.',
            ]);
        }
    }

    private function generateVoucherNumber(int $tenantId, string $type, string $date): string
    {
        $prefix = match ($type) {
            'sales'           => 'SI',
            'purchase'        => 'PI',
            'receipt'         => 'RC',
            'payment'         => 'PY',
            'contra'          => 'CT',
            'debit_note'      => 'DN',
            'credit_note'     => 'CN',
            'reversal'        => 'RV',
            'opening'         => 'OP',
            default           => 'JV',
        };

        $year  = date('y', strtotime($date));
        $month = date('m', strtotime($date));

        $count = Journal::where('tenant_id', $tenantId)
            ->where('voucher_type', $type)
            ->whereYear('date', date('Y', strtotime($date)))
            ->whereMonth('date', $month)
            ->count();

        return sprintf('%s/%s%s/%04d', $prefix, $year, $month, $count + 1);
    }
}
