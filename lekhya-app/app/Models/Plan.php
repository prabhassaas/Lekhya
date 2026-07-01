<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Plan extends Model {
    protected $fillable = ['slug','name','app','edition','tier','client_seat_limit','user_seat_limit','monthly_price','annual_price','has_trial','trial_days','features','is_active'];
    protected $casts = ['features'=>'array','has_trial'=>'boolean','is_active'=>'boolean','monthly_price'=>'decimal:2','annual_price'=>'decimal:2'];
}
