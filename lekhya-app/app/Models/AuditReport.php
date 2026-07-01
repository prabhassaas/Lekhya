<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditReport extends Model {
    protected $table = 'audit_reports';
    protected $fillable = ['tenant_id','client_tenant_id','form_type','financial_year','status','preparer_id','reviewer_id','signer_id','udin_id','dsc_id','report_data','signed_pdf_path','signed_at'];
    protected $casts = ['report_data'=>'array','signed_at'=>'datetime'];
}
