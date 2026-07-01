<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UdinRegister extends Model {
    protected $table = 'udin_register';
    protected $fillable = ['tenant_id','udin','membership_number','document_type','document_date','client_name','client_pan','particulars','status','generated_by','revoked_at'];
    protected $casts = ['document_date'=>'date','revoked_at'=>'datetime'];
}
