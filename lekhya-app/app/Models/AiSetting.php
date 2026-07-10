<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSetting extends Model
{
    protected $fillable = [
        'tenant_id', 'provider', 'api_key', 'text_model', 'vision_model',
        'is_active', 'last_tested_at', 'last_test_status',
    ];

    protected $casts = [
        'api_key'        => 'encrypted', // stored ciphertext; never returned in plaintext to the browser
        'is_active'      => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    // Keep the secret out of any array/JSON serialization by default.
    protected $hidden = ['api_key'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hasKey(): bool
    {
        return filled($this->api_key);
    }

    /** Last 4 chars only, for showing "key is set" without revealing it. */
    public function maskedKey(): ?string
    {
        if (! $this->hasKey()) {
            return null;
        }
        $key = $this->api_key;
        return str_repeat('•', max(0, strlen($key) - 4)) . substr($key, -4);
    }
}
