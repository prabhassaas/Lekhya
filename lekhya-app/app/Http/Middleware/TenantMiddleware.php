<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class TenantMiddleware {
    public function handle(Request $request, Closure $next) {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            return redirect()->route('login')->withErrors(['error' => 'No tenant assigned.']);
        }
        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors(['error' => 'Account disabled.']);
        }
        app()->singleton('current_tenant', fn() => $user->tenant);
        return $next($request);
    }
}
