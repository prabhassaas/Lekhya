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
        'owner_tenant_id',
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

    /** The primary company this one belongs to (null when it IS the primary). */
    public function ownerTenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'owner_tenant_id');
    }

    /** Users who can access this company (multi-company membership). */
    public function members(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')->withPivot('role')->withTimestamps();
    }

    /**
     * The company whose subscription/entitlement governs this one. A secondary
     * company inherits its primary's plan, so one subscription covers them all.
     */
    public function billingTenant(): Tenant
    {
        return $this->owner_tenant_id ? ($this->ownerTenant ?? $this) : $this;
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
        return $this->billingTenant()->entitlements()
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
        return $this->billingTenant()->entitlements()
            ->where('app', 'lekhya')
            ->where('is_active', true)
            ->exists();
    }

    /** The plan currently powering this tenant (active/trial subscription), if any. */
    public function activePlan(): ?Plan
    {
        return $this->billingTenant()->subscriptions()
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

    /**
     * How many Seedha Bill accounts this company may connect. The model is
     * one Seedha Bill account per active connector token; the plan sets how
     * many such connections a company gets. Driven by the plan's
     * features['seedha_bill_connections'] (int, or null = unlimited); trial /
     * no-plan gets one.
     */
    /**
     * How many companies this account may run (multi-company). More generous
     * than the Seedha Bill connection cap so trials can experience it: trial/no
     * plan = 2, Lite = 2, Pro/Lifetime = 5, Pramaan = per client seat, Suite =
     * unlimited. Override with the plan feature 'company_limit'.
     */
    public function companyLimit(): int
    {
        $plan = $this->activePlan();
        if (! $plan) {
            return 2;
        }
        $features = is_array($plan->features) ? $plan->features : [];
        if (array_key_exists('company_limit', $features)) {
            $v = $features['company_limit'];
            return $v === null ? PHP_INT_MAX : max(1, (int) $v);
        }
        if (in_array('all', $features, true) || $plan->tier === 'suite') {
            return PHP_INT_MAX;
        }
        $seats = (int) ($plan->client_seat_limit ?? 1);
        if ($seats >= 9999) {
            return PHP_INT_MAX;
        }
        if ($seats > 1) {
            return $seats;
        }
        return in_array($plan->tier, ['pro', 'lifetime'], true) ? 5 : 2;
    }

    public function seedhaBillConnectionLimit(): int
    {
        $plan = $this->activePlan();
        if (! $plan) {
            return 1; // trial / no subscription — a single connection
        }
        $features = is_array($plan->features) ? $plan->features : [];

        // Explicit override if a plan sets an associative key.
        if (array_key_exists('seedha_bill_connections', $features)) {
            $v = $features['seedha_bill_connections'];
            return $v === null ? PHP_INT_MAX : max(1, (int) $v);
        }

        // Otherwise derive from the plan. "Companies you manage" == Seedha Bill
        // connections, so it tracks the client-seat allowance; the full suite is
        // unlimited and ERP Pro gets a few even on a single seat.
        if (in_array('all', $features, true) || $plan->tier === 'suite') {
            return PHP_INT_MAX;
        }
        $seats = (int) ($plan->client_seat_limit ?? 1);
        if ($seats >= 9999) {
            return PHP_INT_MAX;
        }
        if ($seats > 1) {
            return $seats;
        }
        return $plan->tier === 'pro' ? 3 : 1;
    }

    public function seedhaBillConnectionsUnlimited(): bool
    {
        return $this->seedhaBillConnectionLimit() >= PHP_INT_MAX;
    }

    /** Active (non-revoked, unexpired) connector tokens = live Seedha Bill links. */
    public function seedhaBillConnectionsUsed(): int
    {
        return $this->hasMany(ConnectorToken::class)
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();
    }

    public function canAddSeedhaBillConnection(): bool
    {
        return $this->seedhaBillConnectionsUnlimited()
            || $this->seedhaBillConnectionsUsed() < $this->seedhaBillConnectionLimit();
    }
}
