<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use HasFactory, \App\Models\Concerns\Auditable;

    protected $fillable = [
        'tenant_id', 'fiscal_year_id', 'voucher_number', 'voucher_type',
        'date', 'narration', 'reference', 'total_debit', 'total_credit',
        'is_posted', 'is_reversed', 'reversed_by_journal_id', 'reversal_of_journal_id',
        'created_by', 'approved_by', 'posted_at',
    ];

    protected $casts = [
        'is_posted' => 'boolean',
        'is_reversed' => 'boolean',
        'date' => 'date',
        'posted_at' => 'datetime',
        'total_debit' => 'decimal:4',
        'total_credit' => 'decimal:4',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function fiscalYear(): BelongsTo { return $this->belongsTo(FiscalYear::class); }
    public function lines(): HasMany { return $this->hasMany(JournalLine::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function reversedBy(): BelongsTo { return $this->belongsTo(Journal::class, 'reversed_by_journal_id'); }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.0001;
    }
}
