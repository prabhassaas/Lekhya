<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Models\FiscalYear;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Your account has been disabled.']);
        }

        $user->update(['last_login_at' => now()]);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('marketing.home');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:191',
            'gstin'        => 'nullable|string|size:15',
            'name'         => 'required|string|max:191',
            'email'        => 'required|email|unique:users',
            'phone'        => 'nullable|string|max:15',
            'password'     => 'required|min:8|confirmed',
        ]);

        DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name'  => $request->company_name,
                'slug'  => Str::slug($request->company_name) . '-' . Str::random(4),
                'gstin' => $request->gstin,
            ]);

            // Default 14-day trial entitlement
            Entitlement::create([
                'tenant_id'         => $tenant->id,
                'app'               => 'lekhya',
                'edition'           => 'standard',
                'plan'              => 'practice',
                'client_seat_limit' => 10,
                'trial_ends_at'     => now()->addDays(14),
                'is_active'         => true,
            ]);

            // Default fiscal year
            FiscalYear::create([
                'tenant_id'  => $tenant->id,
                'name'       => date('Y') . '-' . substr(date('Y', strtotime('+1 year')), -2),
                'start_date' => date('Y') . '-04-01',
                'end_date'   => date('Y', strtotime('+1 year')) . '-03-31',
                'is_current' => true,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $request->name,
                'email'     => $request->email,
                'phone'     => $request->phone,
                'password'  => Hash::make($request->password),
                'is_active' => true,
            ]);

            $user->assignRole('owner');

            // Seed chart of accounts
            app(\App\Services\Accounting\ChartOfAccountsSeeder::class)->seed($tenant->id);

            Auth::login($user);
        });

        return redirect()->route('dashboard')->with('success', 'Welcome! Your 14-day free trial has started.');
    }
}
