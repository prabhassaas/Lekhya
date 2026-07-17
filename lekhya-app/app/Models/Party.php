<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Party extends Model {
    use HasFactory, SoftDeletes, \App\Models\Concerns\Auditable;
    protected $fillable = ['tenant_id','type','classification','name','display_name','gstin','pan','email','phone','address','city','state','state_code','pincode','bank_name','bank_account_number','bank_ifsc','bank_account_holder','upi_id','tds_rate','tds_section','receivable_account_id','payable_account_id','is_active'];
    protected $casts = ['is_active' => 'boolean', 'tds_rate' => 'decimal:2'];

    public function hasBankDetails(): bool
    {
        return filled($this->bank_account_number) && filled($this->bank_ifsc);
    }

    /** customer | vendor | supplier | service_provider (AI-detected role). */
    public const CLASSIFICATIONS = [
        'customer'         => ['Customer', 'teal'],
        'vendor'           => ['Vendor', 'amber'],
        'supplier'         => ['Supplier', 'amber'],
        'service_provider' => ['Service Provider', 'indigo'],
    ];

    public function classificationLabel(): string
    {
        return self::CLASSIFICATIONS[$this->classification][0]
            ?? ($this->type === 'customer' ? 'Customer' : ($this->type === 'both' ? 'Customer & Vendor' : 'Vendor'));
    }

    public function classificationColor(): string
    {
        return self::CLASSIFICATIONS[$this->classification][1]
            ?? ($this->type === 'customer' ? 'teal' : ($this->type === 'both' ? 'purple' : 'amber'));
    }

    public function isServiceProvider(): bool
    {
        return $this->classification === 'service_provider';
    }

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function branches() { return $this->hasMany(PartyBranch::class); }
}
