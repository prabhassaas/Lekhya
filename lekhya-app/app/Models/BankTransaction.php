<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BankTransaction extends Model {
    protected $fillable = ['tenant_id','bank_account_id','transaction_date','description','reference','debit','credit','balance','status','journal_line_id','source'];
    protected $casts = ['transaction_date'=>'date','debit'=>'decimal:4','credit'=>'decimal:4','balance'=>'decimal:4'];
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
    public function journalLine() { return $this->belongsTo(JournalLine::class); }
}
