<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A product/service the tenant has billed, with the HSN/SAC + GST rate last used
 * for it. Learned from confirmed invoice lines and used to auto-fill future scans.
 */
class TenantItem extends Model
{
    protected $fillable = ['tenant_id', 'name', 'name_key', 'hsn_sac', 'gst_rate', 'unit', 'times_seen', 'last_seen_at'];

    protected $casts = ['gst_rate' => 'decimal:2', 'last_seen_at' => 'datetime'];

    /** Normalize a description to a stable match key (lowercase, collapsed spaces). */
    public static function keyFor(string $name): string
    {
        return Str::of($name)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }

    /**
     * Remember (or refresh) an item's HSN/rate from a booked line. Only overwrites
     * a known HSN/rate when a new non-empty value is supplied.
     */
    public static function learn(int $tenantId, string $name, ?string $hsn, $gstRate, ?string $unit = null): void
    {
        $name = trim($name);
        $key  = self::keyFor($name);
        if ($key === '') {
            return;
        }

        $item = static::firstOrNew(['tenant_id' => $tenantId, 'name_key' => $key]);
        $item->name = $name;
        $hsn = $hsn !== null ? trim($hsn) : null;
        if ($hsn) {
            $item->hsn_sac = mb_substr($hsn, 0, 15);
        }
        if (is_numeric($gstRate) && (float) $gstRate > 0) {
            $item->gst_rate = (float) $gstRate;
        }
        if ($unit) {
            $item->unit = mb_substr($unit, 0, 20);
        }
        $item->times_seen = ($item->exists ? $item->times_seen : 0) + 1;
        $item->last_seen_at = now();
        $item->save();
    }

    /** Best remembered match for a description, if any. */
    public static function match(int $tenantId, string $name): ?self
    {
        $key = self::keyFor($name);
        return $key === '' ? null : static::where('tenant_id', $tenantId)->where('name_key', $key)->first();
    }
}
