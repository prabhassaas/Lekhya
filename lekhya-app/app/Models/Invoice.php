<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, \App\Models\Concerns\Auditable;

    protected $fillable = [
        'tenant_id', 'fiscal_year_id', 'type', 'document_type', 'invoice_number', 'reference_number',
        'invoice_date', 'due_date', 'party_id', 'party_branch_id', 'place_of_supply', 'is_interstate',
        'reverse_charge', 'status', 'source', 'source_invoice_id',
        'subtotal', 'discount_amount', 'taxable_amount',
        'cgst_amount', 'sgst_amount', 'igst_amount', 'cess_amount',
        'total_tax', 'round_off', 'total_amount', 'paid_amount', 'balance_amount',
        'price_includes_gst', 'tds_rate', 'tds_amount',
        'source_file_path', 'source_file_name', 'extra',
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
        'price_includes_gst' => 'boolean',
        'tds_rate' => 'decimal:2',
        'tds_amount' => 'decimal:2',
        'extra' => 'array',
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

    public const DOCUMENT_TYPES = [
        'tax_invoice'      => 'Tax Invoice',
        'proforma'         => 'Proforma Invoice',
        'delivery_challan' => 'Delivery Challan',
    ];

    public function documentLabel(): string
    {
        if ($this->type === 'purchase') {
            return 'Purchase Bill';
        }
        return self::DOCUMENT_TYPES[$this->document_type] ?? 'Tax Invoice';
    }

    /** Only a tax invoice / purchase bill creates ledger postings & GST liability. */
    public function isAccountingDocument(): bool
    {
        return $this->type === 'purchase' || ($this->document_type ?? 'tax_invoice') === 'tax_invoice';
    }

    /** Path to the original scanned file — direct, or via the AI suggestion that made it. */
    public function originalFilePath(): ?string
    {
        if ($this->source_file_path) {
            return $this->source_file_path;
        }
        $s = AiSuggestion::where('tenant_id', $this->tenant_id)->where('invoice_id', $this->id)->latest('id')->first();
        return $s?->input_context['file_path'] ?? null;
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
