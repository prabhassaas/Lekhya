<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Gstr1Filing extends Model {
    protected $table = 'gstr1_filings';
    protected $fillable = ['tenant_id','return_period','gstin','status','b2b_data','b2c_data','cdnr_data','export_data','total_taxable','total_igst','total_cgst','total_sgst','filed_at','filed_by'];
    protected $casts = ['b2b_data'=>'array','b2c_data'=>'array','cdnr_data'=>'array','filed_at'=>'datetime'];
}
