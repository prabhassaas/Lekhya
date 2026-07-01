<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Gstr2bReconciliation extends Model {
    protected $table = 'gstr2b_reconciliations';
    protected $fillable = ['tenant_id','return_period','invoice_id','gstin_supplier','supplier_invoice_number','invoice_date','invoice_value','igst','cgst','sgst','status','mismatch_details','is_resolved'];
    protected $casts = ['mismatch_details'=>'array','is_resolved'=>'boolean','invoice_date'=>'date'];
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
