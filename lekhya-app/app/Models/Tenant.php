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
}
