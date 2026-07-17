<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * Tenant-facing view of the statutory edit-log (Companies Act audit trail).
 * Read-only — the log is append-only and scoped to the signed-in company.
 */
class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = AuditLog::where('tenant_id', $tenantId)->with('user');

        if ($request->filled('type')) {
            $query->where('event_type', 'like', $request->get('type') . '%');
        }
        if ($request->filled('action') && in_array($request->get('action'), ['created', 'updated', 'deleted'], true)) {
            $query->where('event_type', 'like', '%.' . $request->get('action'));
        }

        $logs = $query->latest('id')->paginate(40)->withQueryString();

        // Distinct record types present, for the filter dropdown (DB-portable).
        $types = AuditLog::where('tenant_id', $tenantId)
            ->distinct()->pluck('event_type')
            ->map(fn ($e) => explode('.', $e)[0])->unique()->sort()->values();

        return view('settings.audit-trail', compact('logs', 'types'));
    }
}
