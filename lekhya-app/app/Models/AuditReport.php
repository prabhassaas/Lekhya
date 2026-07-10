<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditReport extends Model {
    protected $table = 'audit_reports';
    protected $fillable = ['tenant_id','client_tenant_id','form_type','financial_year','status','preparer_id','reviewer_id','signer_id','udin_id','dsc_id','report_data','signed_pdf_path','signed_at'];
    protected $casts = ['report_data'=>'array','signed_at'=>'datetime'];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function clientTenant(): BelongsTo { return $this->belongsTo(Tenant::class, 'client_tenant_id'); }
    public function preparer(): BelongsTo { return $this->belongsTo(User::class, 'preparer_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function signer(): BelongsTo { return $this->belongsTo(User::class, 'signer_id'); }
    public function udin(): BelongsTo { return $this->belongsTo(UdinRegister::class, 'udin_id'); }
    public function dsc(): BelongsTo { return $this->belongsTo(DscCertificate::class, 'dsc_id'); }
    public function workingPapers(): HasMany { return $this->hasMany(WorkingPaper::class); }

    public function isLocked(): bool { return in_array($this->status, ['signed','filed']); }
}
