<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ConnectorConnection extends Model {
    protected $fillable = ['tenant_id','connector_token_id','source_label','source_external_id','mode','status','last_sync_at','last_sync_status','invoices_synced','error_message'];
    protected $casts = ['last_sync_at'=>'datetime'];
    public function token() { return $this->belongsTo(ConnectorToken::class, 'connector_token_id'); }
}
