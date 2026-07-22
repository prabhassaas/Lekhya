<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Log;

/**
 * Thin helper for raising in-app notifications. Never lets a notification
 * failure break the business action that triggered it.
 */
class Notifier
{
    public function toUser(?User $user, string $title, ?string $body = null, ?string $url = null, string $icon = 'fa-bell', string $color = 'navy'): void
    {
        if (! $user) {
            return;
        }
        $this->safely(fn () => $user->notify(new AppNotification($title, $body, $url, $icon, $color)));
    }

    /** Notify every active member of a tenant, optionally excluding the actor. */
    public function toTenant(int $tenantId, string $title, ?string $body = null, ?string $url = null, string $icon = 'fa-bell', string $color = 'navy', ?int $exceptUserId = null): void
    {
        $users = User::where('tenant_id', $tenantId)->where('is_active', true)
            ->when($exceptUserId, fn ($q) => $q->where('id', '!=', $exceptUserId))
            ->get();

        foreach ($users as $user) {
            $this->safely(fn () => $user->notify(new AppNotification($title, $body, $url, $icon, $color)));
        }
    }

    private function safely(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::warning('Notifier failed: ' . $e->getMessage());
        }
    }
}
