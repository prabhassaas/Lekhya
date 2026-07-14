<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatMessage extends Model
{
    protected $fillable = ['tenant_id', 'user_id', 'role', 'body', 'module', 'kind', 'suggestion_id'];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function suggestion(): BelongsTo { return $this->belongsTo(AiSuggestion::class, 'suggestion_id'); }

    /** Recent transcript for one user, oldest first (ready to render top-to-bottom). */
    public static function recentFor(int $tenantId, int $userId, int $limit = 40): array
    {
        return static::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->latest('id')->limit($limit)->get()
            ->reverse()->values()
            ->map(fn ($m) => [
                'role'    => $m->role,
                'text'    => $m->body,
                'href'    => $m->kind === 'scan' && $m->suggestion_id ? route('ai.index') : null,
                'cta'     => $m->kind === 'scan' && $m->suggestion_id ? 'Review & post →' : null,
            ])->all();
    }
}
