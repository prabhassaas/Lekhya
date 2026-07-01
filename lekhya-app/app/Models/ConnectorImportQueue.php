<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ConnectorImportQueue extends Model {
    protected $table = 'connector_import_queue';
    protected $fillable = ['tenant_id','connection_id','source','external_id','raw_payload','normalized_payload','status','error_details','validation_errors','invoice_id','reviewed_by','reviewed_at'];
    protected $casts = ['raw_payload'=>'array','normalized_payload'=>'array','validation_errors'=>'array','reviewed_at'=>'datetime'];
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
}
