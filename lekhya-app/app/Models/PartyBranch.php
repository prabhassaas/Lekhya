<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartyBranch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'party_id', 'label', 'gstin', 'pan', 'email', 'phone',
        'address', 'city', 'state', 'state_code', 'pincode',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
