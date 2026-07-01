<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ConnectorEvent extends Model {
    protected $fillable = ['tenant_id','connection_id','token_id','event_type','description','metadata','actor_id','actor_type'];
    protected $casts = ['metadata'=>'array'];
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
