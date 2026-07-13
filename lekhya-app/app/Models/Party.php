<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Party extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['tenant_id','type','name','display_name','gstin','pan','email','phone','address','city','state','state_code','pincode','bank_name','bank_account_number','bank_ifsc','bank_account_holder','upi_id','receivable_account_id','payable_account_id','is_active'];

    public function hasBankDetails(): bool
    {
        return filled($this->bank_account_number) && filled($this->bank_ifsc);
    }
    protected $casts = ['is_active' => 'boolean'];
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function branches() { return $this->hasMany(PartyBranch::class); }
}
