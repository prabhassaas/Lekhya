<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes, \App\Models\Concerns\Auditable;

    protected $fillable = [
        'tenant_id', 'name', 'sku', 'type', 'dimension', 'quality', 'unit',
        'hsn_sac_code', 'gst_rate', 'sale_price', 'purchase_price',
        'track_inventory', 'opening_stock', 'current_stock', 'is_active',
    ];

    protected $casts = [
        'gst_rate'        => 'decimal:2',
        'sale_price'      => 'decimal:2',
        'purchase_price'  => 'decimal:2',
        'opening_stock'   => 'decimal:3',
        'current_stock'   => 'decimal:3',
        'track_inventory' => 'boolean',
        'is_active'       => 'boolean',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
