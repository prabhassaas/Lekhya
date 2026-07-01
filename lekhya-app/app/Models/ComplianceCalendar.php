<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ComplianceCalendar extends Model {
    protected $table = 'compliance_calendar';
    protected $fillable = ['tenant_id','client_tenant_id','client_name','compliance_type','period','due_date','status','notes','assigned_to','completed_at'];
    protected $casts = ['due_date'=>'date','completed_at'=>'datetime'];
}
