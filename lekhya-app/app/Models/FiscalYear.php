<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FiscalYear extends Model {
    protected $fillable = ['tenant_id','name','start_date','end_date','is_current','is_closed','closed_at'];
    protected $casts = ['start_date'=>'date','end_date'=>'date','is_current'=>'boolean','is_closed'=>'boolean','closed_at'=>'datetime'];
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function journals() { return $this->hasMany(Journal::class); }
}
