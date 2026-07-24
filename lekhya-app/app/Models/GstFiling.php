<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GstFiling extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'type', 'gstin', 'reference',
        'status', 'environment', 'billable', 'meta',
    ];

    protected $casts = [
        'billable' => 'boolean',
        'meta'     => 'array',
    ];

    public const TYPE_LABELS = [
        'irn'        => 'e-Invoice (IRN)',
        'cancel_irn' => 'e-Invoice cancel',
        'eway'       => 'e-Way Bill',
        'gstr1'      => 'GSTR-1 filing',
        'gstr2b'     => 'GSTR-2B fetch',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /** Billable GST API calls this calendar month for a tenant. */
    public static function monthlyCount(int $tenantId): int
    {
        return static::where('tenant_id', $tenantId)
            ->where('billable', true)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }
}
