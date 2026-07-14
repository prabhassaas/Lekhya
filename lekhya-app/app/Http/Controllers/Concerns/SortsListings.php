<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Http\Request;

/**
 * Shared clickable-header sorting for list screens. Reads ?sort=&dir= from the
 * request, applies it only if the column is whitelisted (so no arbitrary
 * ORDER BY), and otherwise falls back to the screen's natural default order.
 */
trait SortsListings
{
    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<string, string|Closure>  $map  public key → DB column, or Closure($query,$dir) for relation/derived sorts
     * @param  ?callable  $default  applied when no valid sort is requested
     */
    protected function applySort($query, Request $request, array $map, ?callable $default = null)
    {
        $sort = (string) $request->query('sort', '');
        $dir  = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sort !== '' && isset($map[$sort])) {
            $target = $map[$sort];
            if ($target instanceof Closure) {
                $target($query, $dir);
            } else {
                $query->orderBy($target, $dir);
            }
            return $query;
        }

        if ($default) {
            $default($query);
        }

        return $query;
    }
}
