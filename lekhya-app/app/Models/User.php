<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'phone', 'password',
        'is_active', 'avatar_path', 'preferences', 'last_login_at',
        'invitation_token', 'invited_at', 'invited_by',
    ];

    protected $hidden = [
        'password', 'remember_token', 'invitation_token',
        'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected static function booted(): void
    {
        // Every user is a member of the company they're created in (covers
        // registration, Google/SSO signup and invited team members). Idempotent.
        static::created(function (self $user) {
            if ($user->tenant_id) {
                $user->companies()->syncWithoutDetaching([$user->tenant_id => ['role' => 'owner']]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'preferences' => 'array',
            'invited_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** 2FA is active only once the user has confirmed a code from their app. */
    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at) && ! is_null($this->two_factor_secret);
    }

    public function invitationPending(): bool
    {
        return ! is_null($this->invitation_token);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Companies (tenants) this user can access and switch between. */
    public function companies(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'company_user')->withPivot('role')->withTimestamps();
    }

    public function companiesCount(): int
    {
        return $this->companies()->count();
    }

    /** How many companies this user's plan allows. */
    public function companyLimit(): int
    {
        return $this->tenant?->companyLimit() ?? 2;
    }

    public function canAddCompany(): bool
    {
        $limit = $this->companyLimit();
        return $limit >= PHP_INT_MAX || $this->companiesCount() < $limit;
    }

    /** The user's primary company (the one holding the subscription). */
    public function primaryCompanyId(): ?int
    {
        return $this->tenant?->owner_tenant_id ?? $this->tenant_id;
    }
}
