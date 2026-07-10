<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeTracker extends Model {
    protected $table = 'notice_tracker';
    protected $fillable = ['tenant_id','client_tenant_id','client_name','notice_type','notice_number','notice_date','response_due_date','authority','subject','status','file_path','assigned_to'];
    protected $casts = ['notice_date'=>'date','response_due_date'=>'date'];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function clientTenant(): BelongsTo { return $this->belongsTo(Tenant::class, 'client_tenant_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }

    public function isOverdue(): bool {
        return $this->response_due_date
            && ! in_array($this->status, ['replied','closed'])
            && $this->response_due_date->isPast();
    }
}
