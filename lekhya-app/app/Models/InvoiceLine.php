<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InvoiceLine extends Model {
    protected $fillable = ['tenant_id','invoice_id','line_order','product_id','description','hsn_sac_code','quantity','unit','rate','discount_percent','discount_amount','taxable_amount','cgst_rate','cgst_amount','sgst_rate','sgst_amount','igst_rate','igst_amount','cess_rate','cess_amount','line_total','account_id','meta'];
    protected $casts = ['quantity'=>'decimal:4','rate'=>'decimal:4','taxable_amount'=>'decimal:4','cgst_amount'=>'decimal:4','sgst_amount'=>'decimal:4','igst_amount'=>'decimal:4','line_total'=>'decimal:4','meta'=>'array'];
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function account() { return $this->belongsTo(Account::class); }
}
