<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function dashboard()
    {
        $totalTenants        = Tenant::count();
        $activeSubscriptions = DB::table('subscriptions')->where('status', 'active')->count();
        $trialTenants        = DB::table('subscriptions')->where('status', 'trial')->count();

        // Monthly revenue estimate: monthly plans at face value, annual plans amortised
        $monthlyMrr = DB::table('subscriptions')
            ->where('status', 'active')
            ->where('billing_cycle', 'monthly')
            ->sum('amount');
        $annualMrr = DB::table('subscriptions')
            ->where('status', 'active')
            ->where('billing_cycle', 'annual')
            ->sum('amount');
        $mrrEstimate = $monthlyMrr + ($annualMrr / 12);

        $stats = [
            'total_tenants'        => $totalTenants,
            'active_subscriptions' => $activeSubscriptions,
            'trial_tenants'        => $trialTenants,
            'mrr_estimate'         => $mrrEstimate,
        ];

        $recentTenants = Tenant::withCount('users')
            ->with(['entitlements' => fn ($q) => $q->where('app', 'lekhya')->where('is_active', true)])
            ->latest()
            ->take(10)
            ->get();

        $planBreakdown = DB::table('entitlements')
            ->select('plan', DB::raw('count(*) as count'))
            ->where('app', 'lekhya')
            ->where('is_active', true)
            ->groupBy('plan')
            ->orderByDesc('count')
            ->get();

        return view('admin.dashboard', compact('stats', 'recentTenants', 'planBreakdown'));
    }

    // -------------------------------------------------------------------------
    // Tenant list
    // -------------------------------------------------------------------------

    public function tenants(Request $request)
    {
        $query = Tenant::withCount('users')
            ->with(['entitlements' => fn ($q) => $q->where('app', 'lekhya')->where('is_active', true)]);

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name',  'like', "%{$term}%")
                  ->orWhere('gstin', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->filled('plan')) {
            $query->whereHas('entitlements', fn ($q) => $q->where('plan', $request->get('plan'))->where('is_active', true));
        }

        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'trial') {
                $query->whereHas('entitlements', fn ($q) => $q->where('is_active', true)->where('trial_ends_at', '>', now()));
            } elseif ($status === 'active') {
                $query->whereHas('entitlements', fn ($q) => $q->where('is_active', true)->whereNull('trial_ends_at'));
            } elseif ($status === 'cancelled') {
                $query->whereHas('entitlements', fn ($q) => $q->where('is_active', false));
            }
        }

        $tenants = $query->latest()->paginate(25)->withQueryString();

        return view('admin.tenants.index', compact('tenants'));
    }

    // -------------------------------------------------------------------------
    // Single tenant detail
    // -------------------------------------------------------------------------

    public function tenant(Tenant $tenant)
    {
        $tenant->loadCount('users')->load([
            'users',
            'entitlements',
        ]);

        $subscription = DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.tenant_id', $tenant->id)
            ->select('subscriptions.*', 'plans.name as plan_name', 'plans.tier as plan_tier')
            ->latest('subscriptions.created_at')
            ->first();

        $recentAuditLogs = DB::table('audit_logs')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->where('audit_logs.tenant_id', $tenant->id)
            ->select('audit_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->latest('audit_logs.created_at')
            ->take(20)
            ->get();

        return view('admin.tenants.show', compact('tenant', 'subscription', 'recentAuditLogs'));
    }

    // -------------------------------------------------------------------------
    // Impersonation
    // -------------------------------------------------------------------------

    public function impersonate(User $user)
    {
        // Guard: do not allow impersonating another super-admin
        if ($user->hasRole('super-admin')) {
            return back()->withErrors(['error' => 'Cannot impersonate a Super Admin.']);
        }

        session(['impersonating' => Auth::id()]);
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', "You are now impersonating {$user->name}.");
    }

    public function stopImpersonating()
    {
        $adminId = session()->pull('impersonating');

        if ($adminId) {
            $admin = User::find($adminId);
            if ($admin) {
                Auth::login($admin);
                return redirect()->route('admin.dashboard')
                    ->with('success', 'Returned to Super Admin session.');
            }
        }

        // Fallback: just log out
        Auth::logout();
        return redirect()->route('login');
    }

    // -------------------------------------------------------------------------
    // Feature flags
    // -------------------------------------------------------------------------

    public function featureFlags()
    {
        $flags = $this->loadFlags();
        return view('admin.feature-flags', compact('flags'));
    }

    public function toggleFeatureFlag(Request $request)
    {
        $request->validate(['flag' => 'required|string|alpha_dash']);

        $flags = $this->loadFlags();
        $key   = $request->get('flag');

        if (array_key_exists($key, $flags)) {
            $flags[$key] = ! $flags[$key];
            $this->saveFlags($flags);
        }

        return back()->with('success', "Flag '{$key}' updated.");
    }

    private function loadFlags(): array
    {
        $path = storage_path('app/feature_flags.json');
        return file_exists($path)
            ? (json_decode(file_get_contents($path), true) ?? $this->defaultFlags())
            : $this->defaultFlags();
    }

    private function saveFlags(array $flags): void
    {
        file_put_contents(storage_path('app/feature_flags.json'), json_encode($flags, JSON_PRETTY_PRINT));
    }

    private function defaultFlags(): array
    {
        return [
            'ai_enabled'            => true,
            'gst_e_invoice'         => false,
            'seedha_bill_connector' => true,
            'tally_migration'       => true,
            'pramaan_ca'            => false,
            'razorpay_billing'      => false,
        ];
    }

    // -------------------------------------------------------------------------
    // Audit log (cross-tenant)
    // -------------------------------------------------------------------------

    public function auditLog(Request $request)
    {
        $query = DB::table('audit_logs')
            ->leftJoin('tenants', 'audit_logs.tenant_id', '=', 'tenants.id')
            ->leftJoin('users',   'audit_logs.user_id',   '=', 'users.id')
            ->select(
                'audit_logs.*',
                'tenants.name as tenant_name',
                'users.name   as actor_name',
                'users.email  as actor_email'
            );

        if ($request->filled('tenant_id')) {
            $query->where('audit_logs.tenant_id', $request->integer('tenant_id'));
        }

        if ($request->filled('event_type')) {
            $query->where('audit_logs.event_type', 'like', '%' . $request->get('event_type') . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('audit_logs.created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('audit_logs.created_at', '<=', $request->get('date_to'));
        }

        $logs    = $query->latest('audit_logs.created_at')->paginate(50)->withQueryString();
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        return view('admin.audit-log', compact('logs', 'tenants'));
    }
}
