<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TallyImport extends Model {
    protected $table = 'tally_imports';
    protected $fillable = ['tenant_id','created_by','filename','file_path','status','summary','errors','total_records','imported_records','failed_records','started_at','completed_at'];
    protected $casts = ['summary'=>'array','errors'=>'array','started_at'=>'datetime','completed_at'=>'datetime'];
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function createdBy() { return $this->belongsTo(User::class,'created_by'); }
}
