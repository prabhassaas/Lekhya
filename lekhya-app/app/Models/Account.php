<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes, \App\Models\Concerns\Auditable;

    protected $fillable = [
        'tenant_id', 'code', 'name', 'type', 'sub_type', 'parent_id',
        'level', 'is_ledger', 'is_system', 'is_active', 'description',
        'opening_balance', 'opening_balance_type',
    ];

    protected $casts = [
        'is_ledger' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:4',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function parent(): BelongsTo { return $this->belongsTo(Account::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(Account::class, 'parent_id'); }
    public function journalLines(): HasMany { return $this->hasMany(JournalLine::class); }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Compute balance from journal lines
    public function getBalance(?string $from = null, ?string $to = null): array
    {
        $query = $this->journalLines()
            ->whereHas('journal', fn($q) => $q->where('is_posted', true));

        // Journal dates are stored as full datetimes; a plain Y-m-d boundary compared as
        // TEXT (SQLite) would otherwise exclude same-day entries, since e.g. the string
        // "2026-07-10 00:00:00" sorts after its own prefix "2026-07-10".
        if ($from) $query->whereHas('journal', fn($q) => $q->where('date', '>=', Carbon::parse($from)->startOfDay()));
        if ($to) $query->whereHas('journal', fn($q) => $q->where('date', '<=', Carbon::parse($to)->endOfDay()));

        $debit = $query->sum('debit');
        $credit = $query->sum('credit');

        return ['debit' => $debit, 'credit' => $credit, 'net' => $debit - $credit];
    }
}
