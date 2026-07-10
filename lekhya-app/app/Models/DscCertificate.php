<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DscCertificate extends Model {
    protected $table = 'dsc_certificates';
    protected $fillable = ['tenant_id','holder_name','cn','valid_from','valid_to','certificate_path','is_active'];
    protected $casts = ['valid_from'=>'date','valid_to'=>'date','is_active'=>'boolean'];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }

    public function isExpired(): bool { return $this->valid_to->isPast(); }

    public function expiringSoon(int $days = 30): bool {
        return ! $this->isExpired() && $this->valid_to->isBefore(now()->addDays($days));
    }
}
