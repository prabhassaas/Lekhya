<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BankAccount extends Model {
    protected $fillable = ['tenant_id','account_id','bank_name','account_number','ifsc_code','branch','account_type','opening_balance','is_active'];
    protected $casts = ['opening_balance'=>'decimal:4','is_active'=>'boolean'];
    public function account() { return $this->belongsTo(Account::class); }
    public function transactions() { return $this->hasMany(BankTransaction::class); }
}
