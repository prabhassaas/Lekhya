<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GstSetting extends Model
{
    protected $fillable = [
        'tenant_id', 'gstin', 'gsp', 'environment',
        'einvoice_username', 'einvoice_password',
        'ewb_username', 'ewb_password', 'returns_username',
        'status', 'connected_at', 'last_verified_at',
    ];

    protected $casts = [
        'einvoice_password' => 'encrypted', // ciphertext at rest, never serialized
        'ewb_password'      => 'encrypted',
        'connected_at'      => 'datetime',
        'last_verified_at'  => 'datetime',
    ];

    protected $hidden = ['einvoice_password', 'ewb_password'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** A connection is live once a GSTIN and at least one credential set are stored. */
    public function isConnected(): bool
    {
        return $this->status === 'connected'
            && filled($this->gstin)
            && ($this->hasCredentials('einvoice') || $this->hasCredentials('ewb'));
    }

    public function hasCredentials(string $system): bool
    {
        return $system === 'ewb'
            ? filled($this->ewb_username) && filled($this->ewb_password)
            : filled($this->einvoice_username) && filled($this->einvoice_password);
    }

    /** Decrypted [username, password] for the real GSP call — resolved per tenant. */
    public function credentials(string $system): array
    {
        return $system === 'ewb'
            ? ['username' => $this->ewb_username, 'password' => $this->ewb_password]
            : ['username' => $this->einvoice_username, 'password' => $this->einvoice_password];
    }

    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }
}
