<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsage extends Model
{
    protected $table = 'ai_usage';
    protected $fillable = ['tenant_id', 'user_id', 'type', 'driver', 'tokens', 'billable'];
    protected $casts = ['billable' => 'boolean'];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }

    /** Billable AI calls this calendar month for a tenant. */
    public static function monthlyCount(int $tenantId): int
    {
        return static::where('tenant_id', $tenantId)
            ->where('billable', true)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }
}
