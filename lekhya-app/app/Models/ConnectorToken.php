<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ConnectorToken extends Model {
    protected $fillable = ['tenant_id','created_by','token_hash','label','scope','expires_at','is_active','last_used_at','revoked_by','revoked_at'];
    protected $casts = ['scope'=>'array','expires_at'=>'datetime','last_used_at'=>'datetime','revoked_at'=>'datetime','is_active'=>'boolean'];
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
