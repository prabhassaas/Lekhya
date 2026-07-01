<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HsnSacCode extends Model {
    protected $table = 'hsn_sac_codes';
    protected $fillable = ['code','type','description','cgst_rate','sgst_rate','igst_rate','cess_rate','is_active'];
    protected $casts = ['cgst_rate'=>'decimal:2','sgst_rate'=>'decimal:2','igst_rate'=>'decimal:2','cess_rate'=>'decimal:2','is_active'=>'boolean'];
}
