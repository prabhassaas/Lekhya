<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Party extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['tenant_id','type','name','display_name','gstin','pan','email','phone','address','city','state','state_code','pincode','receivable_account_id','payable_account_id','is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
}
