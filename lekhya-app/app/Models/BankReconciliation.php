<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BankReconciliation extends Model {
    protected $table = 'bank_reconciliations';
    protected $fillable = ['tenant_id','bank_account_id','statement_date','statement_balance','book_balance','difference','is_reconciled','reconciled_at','reconciled_by'];
    protected $casts = ['statement_date'=>'date','reconciled_at'=>'datetime','is_reconciled'=>'boolean','statement_balance'=>'decimal:4','book_balance'=>'decimal:4'];
}
