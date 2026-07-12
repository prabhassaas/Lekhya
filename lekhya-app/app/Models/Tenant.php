<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'gstin', 'pan', 'phone', 'email',
        'address', 'city', 'state', 'state_code', 'pincode', 'country',
        'logo_path', 'letterhead_path', 'fiscal_year_start', 'currency', 'settings', 'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($t) => $t->ulid = (string) Str::ulid());
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(Entitlement::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function aiSetting()
    {
        return $this->hasOne(AiSetting::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function parties(): HasMany
    {
        return $this->hasMany(Party::class);
    }

    public function hasEntitlement(string $app, string $edition): bool
    {
        return $this->entitlements()
            ->where('app', $app)
            ->where('edition', $edition)
            ->where('is_active', true)
            ->exists();
    }

    public function isPramaan(): bool
    {
        return $this->hasEntitlement('lekhya', 'pramaan');
    }

    /** AI is auto-enabled for any tenant on an active subscription or trial. */
    public function aiEnabled(): bool
    {
        return $this->entitlements()
            ->where('app', 'lekhya')
            ->where('is_active', true)
            ->exists();
    }

    /** The plan currently powering this tenant (active/trial subscription), if any. */
    public function activePlan(): ?Plan
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->latest()
            ->first()?->plan;
    }

    /** Monthly AI request allowance (invoice scans, NL queries, auto-coding). */
    public function aiCreditLimit(): int
    {
        $plan = $this->activePlan();
        if (! $plan) {
            return 50; // trial / no subscription — small starter allowance
        }
        return $plan->ai_credits === null ? PHP_INT_MAX : (int) $plan->ai_credits; // null = unlimited
    }

    public function aiCreditsUnlimited(): bool
    {
        return $this->aiCreditLimit() >= PHP_INT_MAX;
    }

    public function aiCreditsUsed(): int
    {
        return AiUsage::monthlyCount($this->id);
    }

    public function aiCreditsRemaining(): int
    {
        return max(0, $this->aiCreditLimit() - $this->aiCreditsUsed());
    }

    /** Only entitled tenants that have run through their monthly allowance. */
    public function aiCreditsExhausted(): bool
    {
        return $this->aiEnabled() && ! $this->aiCreditsUnlimited() && $this->aiCreditsRemaining() <= 0;
    }
}
