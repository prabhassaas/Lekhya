<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id', 'plan_id', 'status', 'billing_cycle', 'amount',
        'razorpay_subscription_id', 'razorpay_plan_id',
        'trial_ends_at', 'current_period_start', 'current_period_end',
        'cancelled_at', 'ends_at', 'dunning_count', 'next_dunning_at',
    ];

    protected $casts = [
        'amount'               => 'decimal:2',
        'trial_ends_at'        => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end'   => 'datetime',
        'cancelled_at'         => 'datetime',
        'ends_at'              => 'datetime',
        'next_dunning_at'      => 'datetime',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function invoices(): HasMany { return $this->hasMany(SubscriptionInvoice::class); }
}
