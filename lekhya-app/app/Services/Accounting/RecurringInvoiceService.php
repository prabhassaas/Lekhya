<?php

namespace App\Services\Accounting;

use App\Models\FiscalYear;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns a recurring schedule into draft invoices, one per due period. The
 * schedule carries a snapshot of the source invoice (party + computed lines +
 * totals), so generation never re-derives tax — it copies the agreed figures.
 */
class RecurringInvoiceService
{
    public function __construct(private InvoicePostingService $posting) {}

    /** Build a schedule from an existing invoice, snapshotting its lines & totals. */
    public function snapshotFrom(Invoice $invoice, array $opts): RecurringInvoice
    {
        $invoice->loadMissing('lines');

        $dueDays = ($invoice->due_date && $invoice->invoice_date)
            ? $invoice->invoice_date->diffInDays($invoice->due_date)
            : null;

        $header = [
            'place_of_supply' => $invoice->place_of_supply,
            'is_interstate'   => (bool) $invoice->is_interstate,
            'subtotal'        => (float) $invoice->subtotal,
            'taxable_amount'  => (float) $invoice->taxable_amount,
            'cgst_amount'     => (float) $invoice->cgst_amount,
            'sgst_amount'     => (float) $invoice->sgst_amount,
            'igst_amount'     => (float) $invoice->igst_amount,
            'cess_amount'     => (float) $invoice->cess_amount,
            'total_tax'       => (float) $invoice->total_tax,
            'round_off'       => (float) $invoice->round_off,
            'total_amount'    => (float) $invoice->total_amount,
            'tds_amount'      => $invoice->tds_amount !== null ? (float) $invoice->tds_amount : null,
            'due_days'        => $dueDays,
        ];

        $lines = $invoice->lines->map(fn ($l) => [
            'product_id'       => $l->product_id,
            'description'      => $l->description,
            'hsn_sac_code'     => $l->hsn_sac_code,
            'quantity'         => (float) $l->quantity,
            'unit'             => $l->unit,
            'rate'             => (float) $l->rate,
            'discount_percent' => (float) $l->discount_percent,
            'discount_amount'  => (float) $l->discount_amount,
            'taxable_amount'   => (float) $l->taxable_amount,
            'cgst_rate'        => (float) $l->cgst_rate, 'cgst_amount' => (float) $l->cgst_amount,
            'sgst_rate'        => (float) $l->sgst_rate, 'sgst_amount' => (float) $l->sgst_amount,
            'igst_rate'        => (float) $l->igst_rate, 'igst_amount' => (float) $l->igst_amount,
            'cess_rate'        => (float) $l->cess_rate, 'cess_amount' => (float) $l->cess_amount,
            'line_total'       => (float) $l->line_total,
            'meta'             => $l->meta,
        ])->values()->all();

        $start = Carbon::parse($opts['start_date'] ?? now()->toDateString())->startOfDay();

        return RecurringInvoice::create([
            'tenant_id'          => $invoice->tenant_id,
            'title'              => $opts['title'] ?? trim(($invoice->party->name ?? 'Recurring') . ' — ' . $invoice->documentLabel()),
            'party_id'           => $invoice->party_id,
            'party_branch_id'    => $invoice->party_branch_id,
            'type'               => $invoice->type,
            'document_type'      => $invoice->document_type ?? 'tax_invoice',
            'frequency'          => $opts['frequency'] ?? 'monthly',
            'interval_count'     => max(1, (int) ($opts['interval_count'] ?? 1)),
            'start_date'         => $start->toDateString(),
            'next_run_date'      => $start->toDateString(),
            'end_date'           => ! empty($opts['end_date']) ? Carbon::parse($opts['end_date'])->toDateString() : null,
            'occurrences_limit'  => ! empty($opts['occurrences_limit']) ? (int) $opts['occurrences_limit'] : null,
            'status'             => 'active',
            'price_includes_gst' => (bool) $invoice->price_includes_gst,
            'tds_rate'           => $invoice->tds_rate !== null ? (float) $invoice->tds_rate : null,
            'auto_post'          => (bool) ($opts['auto_post'] ?? false),
            'notes'              => $invoice->notes,
            'terms'              => $invoice->terms,
            'header'             => $header,
            'lines'              => $lines,
            'created_by'         => $opts['created_by'] ?? auth()->id() ?? $invoice->created_by,
        ]);
    }

    /** Raise one draft invoice for the schedule's current period and advance it. */
    public function generate(RecurringInvoice $schedule): ?Invoice
    {
        $runDate = ($schedule->next_run_date ?: now())->toDateString();
        $fy = $this->fiscalYearFor($schedule->tenant_id, $runDate);
        if (! $fy) {
            Log::warning("Recurring #{$schedule->id}: no fiscal year covering {$runDate}; skipped.");
            return null;
        }

        $header = $schedule->header ?? [];
        $docType = $schedule->document_type ?: 'tax_invoice';
        $isAccounting = $schedule->type === 'purchase' || $docType === 'tax_invoice';
        $total = (float) ($header['total_amount'] ?? 0);
        $dueDate = isset($header['due_days']) && $header['due_days'] !== null
            ? Carbon::parse($runDate)->addDays((int) $header['due_days'])->toDateString()
            : null;

        $invoice = DB::transaction(function () use ($schedule, $fy, $header, $docType, $isAccounting, $total, $runDate, $dueDate) {
            $invoice = Invoice::create([
                'tenant_id'           => $schedule->tenant_id,
                'fiscal_year_id'      => $fy->id,
                'type'                => $schedule->type ?: 'sales',
                'document_type'       => $docType,
                'invoice_number'      => Invoice::nextNumberFor($schedule->tenant_id, $schedule->type ?: 'sales', $docType),
                'invoice_date'        => $runDate,
                'due_date'            => $dueDate,
                'party_id'            => $schedule->party_id,
                'party_branch_id'     => $schedule->party_branch_id,
                'place_of_supply'     => $header['place_of_supply'] ?? null,
                'is_interstate'       => (bool) ($header['is_interstate'] ?? false),
                'status'              => 'draft',
                'source'              => 'recurring',
                'subtotal'            => $header['subtotal'] ?? 0,
                'taxable_amount'      => $header['taxable_amount'] ?? 0,
                'cgst_amount'         => $header['cgst_amount'] ?? 0,
                'sgst_amount'         => $header['sgst_amount'] ?? 0,
                'igst_amount'         => $header['igst_amount'] ?? 0,
                'cess_amount'         => $header['cess_amount'] ?? 0,
                'total_tax'           => $header['total_tax'] ?? 0,
                'round_off'           => $header['round_off'] ?? 0,
                'total_amount'        => $total,
                'paid_amount'         => 0,
                'balance_amount'      => $isAccounting ? $total : 0,
                'price_includes_gst'  => (bool) $schedule->price_includes_gst,
                'tds_rate'            => $schedule->tds_rate,
                'tds_amount'          => $header['tds_amount'] ?? null,
                'notes'               => $schedule->notes,
                'terms'               => $schedule->terms,
                'recurring_invoice_id' => $schedule->id,
                'created_by'          => $schedule->created_by,
            ]);

            foreach (($schedule->lines ?? []) as $i => $line) {
                $invoice->lines()->create(array_merge($line, [
                    'tenant_id'  => $schedule->tenant_id,
                    'line_order' => $i,
                ]));
            }

            // Advance the cadence and retire the schedule once it's run its course.
            $schedule->last_invoice_id       = $invoice->id;
            $schedule->last_generated_at      = now();
            $schedule->occurrences_generated += 1;
            $schedule->next_run_date          = $schedule->nextDateAfter($schedule->next_run_date);
            if ($schedule->isExhausted()) {
                $schedule->status = 'ended';
            }
            $schedule->save();

            return $invoice;
        });

        // Auto-post outside the create transaction so a posting failure leaves a
        // reviewable draft rather than losing the whole run.
        if ($invoice && $schedule->auto_post && $isAccounting) {
            try {
                $this->posting->post($invoice, $schedule->created_by);
            } catch (\Throwable $e) {
                Log::warning("Recurring #{$schedule->id}: auto-post of invoice #{$invoice->id} failed — left as draft. {$e->getMessage()}");
            }
        }

        if ($invoice) {
            app(\App\Services\Notification\Notifier::class)->toTenant(
                $schedule->tenant_id,
                'Recurring invoice raised',
                "{$invoice->invoice_number} — {$schedule->title}",
                route('accounting.invoices.show', $invoice),
                'fa-repeat',
                'indigo',
            );
        }

        return $invoice;
    }

    /** Generate every schedule that is due (optionally for one tenant). Returns invoices raised. */
    public function runDue(?int $tenantId = null): int
    {
        $query = RecurringInvoice::due();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $count = 0;
        foreach ($query->get() as $schedule) {
            // Catch up every missed period in one pass (capped for safety).
            $guard = 0;
            while ($schedule->isDue() && $guard++ < 366) {
                if ($this->generate($schedule)) {
                    $count++;
                }
                $schedule->refresh();
            }
        }

        return $count;
    }

    /** Fiscal year covering the date, else the current one, else the latest. */
    private function fiscalYearFor(int $tenantId, string $date): ?FiscalYear
    {
        return FiscalYear::where('tenant_id', $tenantId)
                ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first()
            ?? FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->first()
            ?? FiscalYear::where('tenant_id', $tenantId)->orderByDesc('start_date')->first();
    }
}
