<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoice extends Model
{
    protected $fillable = [
        'tenant_id', 'subscription_id', 'invoice_number', 'invoice_date',
        'amount', 'gst_amount', 'total_amount', 'status',
        'razorpay_payment_id', 'razorpay_order_id', 'paid_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'amount'       => 'decimal:2',
        'gst_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at'      => 'datetime',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
}
