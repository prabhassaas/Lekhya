<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Records an immutable edit-log entry for every create / update / delete of the
 * model — the audit trail required by the Companies (Accounts) Rules, 2014,
 * Rule 3. Writing is unconditional (cannot be turned off from the UI). A
 * failure here is logged but never blocks the underlying financial write.
 */
trait Auditable
{
    /** Attributes that must never appear in the log (noise / secrets). */
    protected function auditExcluded(): array
    {
        return ['created_at', 'updated_at', 'remember_token', 'password', 'api_key', 'token_hash', 'signed_qr'];
    }

    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAudit('created', null, $model->auditableSubset($model->getAttributes()));
        });

        static::updated(function ($model) {
            $before = [];
            $after  = [];
            foreach ($model->getChanges() as $key => $new) {
                if (in_array($key, $model->auditExcluded(), true)) {
                    continue;
                }
                $before[$key] = $model->getOriginal($key);
                $after[$key]  = $new;
            }
            if (! empty($after)) {
                $model->writeAudit('updated', $before, $after);
            }
        });

        static::deleted(function ($model) {
            $model->writeAudit('deleted', $model->auditableSubset($model->getOriginal()), null);
        });
    }

    protected function auditableSubset(array $attrs): array
    {
        return array_diff_key($attrs, array_flip($this->auditExcluded()));
    }

    protected function writeAudit(string $action, ?array $before, ?array $after): void
    {
        try {
            $req = request();
            AuditLog::create([
                'tenant_id'      => $this->tenant_id ?? (auth()->check() ? auth()->user()->tenant_id : null),
                'user_id'        => auth()->id(),
                'event_type'     => Str::snake(class_basename(static::class)) . '.' . $action,
                'auditable_type' => static::class,
                'auditable_id'   => $this->getKey(),
                'before'         => $before,
                'after'          => $after,
                'ip_address'     => $req?->ip(),
                'user_agent'     => $req ? substr((string) $req->userAgent(), 0, 255) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed', ['model' => static::class, 'error' => $e->getMessage()]);
        }
    }
}
