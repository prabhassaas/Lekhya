<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceCalendar extends Model {
    protected $table = 'compliance_calendar';
    protected $fillable = ['tenant_id','client_tenant_id','client_name','compliance_type','period','due_date','status','notes','assigned_to','completed_at'];
    protected $casts = ['due_date'=>'date','completed_at'=>'datetime'];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function clientTenant(): BelongsTo { return $this->belongsTo(Tenant::class, 'client_tenant_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }

    public function isOverdue(): bool {
        return $this->status !== 'filed' && $this->due_date->isPast();
    }
}
