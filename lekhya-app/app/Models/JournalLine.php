<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    protected $fillable = [
        'tenant_id', 'journal_id', 'account_id', 'line_order',
        'debit', 'credit', 'narration',
    ];

    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
    ];

    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
}
