<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [
        'tenant_id', 'fiscal_year_id', 'type', 'party_id', 'account_id',
        'date', 'amount', 'tds_amount', 'payment_mode', 'reference_number',
        'transaction_reference', 'narration', 'journal_id', 'created_by',
    ];

    protected $casts = [
        'date'       => 'date',
        'amount'     => 'decimal:4',
        'tds_amount' => 'decimal:4',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function party(): BelongsTo { return $this->belongsTo(Party::class); }
    public function ledgerAccount(): BelongsTo { return $this->belongsTo(Account::class, 'account_id'); }
    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function allocations(): HasMany { return $this->hasMany(PaymentAllocation::class); }

    public function isReceipt(): bool { return $this->type === 'receipt'; }
    public function label(): string { return $this->type === 'receipt' ? 'Receipt' : 'Payment'; }
}
