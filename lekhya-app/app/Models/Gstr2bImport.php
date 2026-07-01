<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Gstr2bImport extends Model {
    protected $table = 'gstr2b_imports';
    protected $fillable = ['tenant_id','return_period','gstin','data','imported_at'];
    protected $casts = ['data'=>'array','imported_at'=>'datetime'];
}
