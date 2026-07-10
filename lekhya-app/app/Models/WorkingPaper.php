<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingPaper extends Model {
    protected $table = 'working_papers';
    protected $fillable = ['tenant_id','audit_report_id','title','category','file_path','file_name','mime_type','uploaded_by'];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function auditReport(): BelongsTo { return $this->belongsTo(AuditReport::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
