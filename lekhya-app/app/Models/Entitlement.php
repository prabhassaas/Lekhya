<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Entitlement extends Model {
    protected $fillable = ['tenant_id','app','edition','plan','client_seat_limit','client_seats_used','trial_ends_at','active_until','is_active'];
    protected $casts = ['trial_ends_at'=>'datetime','active_until'=>'datetime','is_active'=>'boolean'];
    public function tenant() { return $this->belongsTo(Tenant::class); }
}
