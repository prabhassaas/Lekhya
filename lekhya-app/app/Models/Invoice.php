<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'fiscal_year_id', 'type', 'invoice_number', 'reference_number',
        'invoice_date', 'due_date', 'party_id', 'party_branch_id', 'place_of_supply', 'is_interstate',
        'reverse_charge', 'status', 'source', 'source_invoice_id',
        'subtotal', 'discount_amount', 'taxable_amount',
        'cgst_amount', 'sgst_amount', 'igst_amount', 'cess_amount',
        'total_tax', 'round_off', 'total_amount', 'paid_amount', 'balance_amount',
        'irn', 'ack_number', 'ack_date', 'signed_qr', 'eway_bill_number',
        'notes', 'terms', 'journal_id', 'created_by', 'posted_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'ack_date' => 'datetime',
        'posted_at' => 'datetime',
        'is_interstate' => 'boolean',
        'reverse_charge' => 'boolean',
        'subtotal' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'balance_amount' => 'decimal:4',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function party(): BelongsTo { return $this->belongsTo(Party::class); }
    public function branch(): BelongsTo { return $this->belongsTo(PartyBranch::class, 'party_branch_id'); }
    public function lines(): HasMany { return $this->hasMany(InvoiceLine::class); }
    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function isLocked(): bool
    {
        return in_array($this->status, ['locked', 'posted', 'cancelled']);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
