<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use App\Models\Tenant;
use App\Services\Accounting\ChartOfAccountsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Multi-company: list, switch and create companies. One user can run several
 * companies (each its own GSTIN + books) up to the plan's limit; secondary
 * companies inherit the primary's subscription, so they cost nothing extra.
 */
class CompanyController extends Controller
{
    public function index()
    {
        $user      = auth()->user();
        $companies = $user->companies()->orderBy('name')->get();

        return view('settings.companies', [
            'companies' => $companies,
            'activeId'  => $user->tenant_id,
            'canAdd'    => $user->canAddCompany(),
            'limit'     => $user->companyLimit(),
            'used'      => $companies->count(),
        ]);
    }

    public function switchTo(Tenant $company)
    {
        $user = auth()->user();
        abort_unless($user->companies()->whereKey($company->id)->exists(), 403);

        $user->forceFill(['tenant_id' => $company->id])->save();

        return redirect()->route('dashboard')->with('success', "Switched to {$company->name}.");
    }

    public function create()
    {
        abort_unless(auth()->user()->canAddCompany(), 403, 'Your plan does not allow more companies. Upgrade to add more.');
        return view('settings.company-create');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->canAddCompany(), 403, 'Your plan does not allow more companies. Upgrade to add more.');

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'gstin' => 'nullable|string|size:15',
        ]);

        $company = DB::transaction(function () use ($user, $data) {
            $gstin  = $data['gstin'] ?? null;
            $tenant = Tenant::create([
                'owner_tenant_id' => $user->primaryCompanyId(), // inherits the primary's plan
                'name'            => $data['name'],
                'slug'            => Str::slug($data['name']) . '-' . Str::random(4),
                'gstin'           => $gstin,
                'pan'             => $gstin && strlen($gstin) >= 12 ? substr($gstin, 2, 10) : null,
                'state_code'      => $gstin && strlen($gstin) >= 2 ? substr($gstin, 0, 2) : null,
                'is_active'       => true,
            ]);

            FiscalYear::create([
                'tenant_id'  => $tenant->id,
                'name'       => date('Y') . '-' . substr(date('Y', strtotime('+1 year')), -2),
                'start_date' => date('Y') . '-04-01',
                'end_date'   => date('Y', strtotime('+1 year')) . '-03-31',
                'is_current' => true,
            ]);

            app(ChartOfAccountsSeeder::class)->seed($tenant->id);

            $user->companies()->syncWithoutDetaching([$tenant->id => ['role' => 'owner']]);
            $user->forceFill(['tenant_id' => $tenant->id])->save(); // switch into it

            return $tenant;
        });

        return redirect()->route('dashboard')->with('success', "Company “{$company->name}” created — you're now working in it.");
    }
}
