<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable edit-log entry — one row per create/update/delete of a financial
 * record. Satisfies the audit-trail mandate of the Companies (Accounts) Rules,
 * 2014 (Rule 3): every change is recorded with the actor, time and before/after
 * values, and the log is written unconditionally (cannot be disabled in-app).
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null; // append-only; only created_at

    protected $fillable = [
        'tenant_id', 'user_id', 'event_type', 'auditable_type', 'auditable_id',
        'before', 'after', 'ip_address', 'user_agent',
    ];

    protected $casts = ['before' => 'array', 'after' => 'array'];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function auditable() { return $this->morphTo(); }
}
