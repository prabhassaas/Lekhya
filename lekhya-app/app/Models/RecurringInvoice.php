<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringInvoice extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [
        'tenant_id', 'title', 'party_id', 'party_branch_id', 'type', 'document_type',
        'frequency', 'interval_count', 'start_date', 'next_run_date', 'end_date',
        'occurrences_limit', 'occurrences_generated', 'status',
        'price_includes_gst', 'tds_rate', 'auto_post', 'notes', 'terms',
        'header', 'lines', 'last_invoice_id', 'last_generated_at', 'created_by',
    ];

    protected $casts = [
        'start_date'         => 'date',
        'next_run_date'      => 'date',
        'end_date'           => 'date',
        'last_generated_at'  => 'datetime',
        'price_includes_gst' => 'boolean',
        'auto_post'          => 'boolean',
        'tds_rate'           => 'decimal:2',
        'header'             => 'array',
        'lines'              => 'array',
    ];

    public const FREQUENCIES = [
        'weekly'    => 'Weekly',
        'monthly'   => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly'    => 'Yearly',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function party(): BelongsTo { return $this->belongsTo(Party::class); }
    public function branch(): BelongsTo { return $this->belongsTo(PartyBranch::class, 'party_branch_id'); }
    public function lastInvoice(): BelongsTo { return $this->belongsTo(Invoice::class, 'last_invoice_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class, 'recurring_invoice_id'); }

    public function frequencyLabel(): string
    {
        $base = self::FREQUENCIES[$this->frequency] ?? ucfirst($this->frequency);
        return $this->interval_count > 1
            ? "Every {$this->interval_count} " . rtrim(strtolower($base), 'ly') . 's'
            : $base;
    }

    /** Advance a date by one cadence step (frequency × interval). */
    public function nextDateAfter(\Carbon\Carbon $date): \Carbon\Carbon
    {
        $n = max(1, (int) $this->interval_count);
        return match ($this->frequency) {
            'weekly'    => $date->copy()->addWeeks($n),
            'quarterly' => $date->copy()->addMonthsNoOverflow(3 * $n),
            'yearly'    => $date->copy()->addYearsNoOverflow($n),
            default     => $date->copy()->addMonthsNoOverflow($n), // monthly
        };
    }

    /** Has this schedule run its course (end date passed or occurrence cap hit)? */
    public function isExhausted(): bool
    {
        if ($this->occurrences_limit && $this->occurrences_generated >= $this->occurrences_limit) {
            return true;
        }
        return $this->end_date && $this->next_run_date && $this->next_run_date->gt($this->end_date);
    }

    public function isDue(?\Carbon\Carbon $asOf = null): bool
    {
        $asOf = $asOf ?: now();
        return $this->status === 'active'
            && $this->next_run_date
            && $this->next_run_date->startOfDay()->lte($asOf->copy()->endOfDay())
            && ! $this->isExhausted();
    }

    public function scopeDue($query, ?\Carbon\Carbon $asOf = null)
    {
        $asOf = $asOf ?: now();
        return $query->where('status', 'active')->whereDate('next_run_date', '<=', $asOf->toDateString());
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
