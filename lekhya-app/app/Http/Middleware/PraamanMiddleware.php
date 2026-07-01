<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class PraamanMiddleware {
    public function handle(Request $request, Closure $next) {
        if (!auth()->user()->tenant?->isPramaan()) {
            return redirect()->route('dashboard')->with('error', 'Lekhya Pramaan (CA edition) required for this feature.');
        }
        return $next($request);
    }
}
