<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankPaymentTemplate extends Model
{
    protected $fillable = ['tenant_id', 'name', 'headers', 'mapping', 'created_by'];

    protected $casts = ['headers' => 'array', 'mapping' => 'array'];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
}
