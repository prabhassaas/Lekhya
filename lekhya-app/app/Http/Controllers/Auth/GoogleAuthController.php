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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function callback()
    {
        return view('auth.google-callback');
    }

    public function verify(Request $request)
    {
        $token = $request->input('access_token');
        if (! $token) {
            return response()->json(['error' => 'No access token provided.'], 422);
        }

        $supabaseUrl = rtrim(config('services.supabase.url', ''), '/');
        $anonKey     = config('services.supabase.anon_key', '');

        $resp = Http::withHeaders([
            'apikey'        => $anonKey,
            'Authorization' => 'Bearer ' . $token,
        ])->get("{$supabaseUrl}/auth/v1/user");

        if (! $resp->successful()) {
            return response()->json(['error' => 'Google sign-in could not be verified. Please try again.'], 401);
        }

        $data  = $resp->json();
        $email = $data['email'] ?? null;
        $meta  = $data['user_metadata'] ?? [];
        $name  = $meta['full_name'] ?? $meta['name'] ?? ($email ? explode('@', $email)[0] : 'User');

        if (! $email) {
            return response()->json(['error' => 'No email address was returned from Google.'], 422);
        }

        $isNew = false;

        $user = DB::transaction(function () use ($email, $name, &$isNew) {
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update(['last_login_at' => now()]);
                return $user;
            }

            $isNew = true;

            $tenant = Tenant::create([
                'name'  => $name,
                'slug'  => Str::slug($name) . '-' . Str::random(4),
                'gstin' => null,
            ]);

            Entitlement::create([
                'tenant_id'         => $tenant->id,
                'app'               => 'lekhya',
                'edition'           => 'standard',
                'plan'              => 'practice',
                'client_seat_limit' => 10,
                'trial_ends_at'     => now()->addDays(14),
                'is_active'         => true,
            ]);

            FiscalYear::create([
                'tenant_id'  => $tenant->id,
                'name'       => date('Y') . '-' . substr(date('Y', strtotime('+1 year')), -2),
                'start_date' => date('Y') . '-04-01',
                'end_date'   => date('Y', strtotime('+1 year')) . '-03-31',
                'is_current' => true,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $name,
                'email'     => $email,
                'phone'     => null,
                'password'  => bcrypt(Str::random(32)),
                'is_active' => true,
            ]);

            $user->assignRole('owner');
            app(\App\Services\Accounting\ChartOfAccountsSeeder::class)->seed($tenant->id);

            return $user;
        });

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($isNew) {
            session()->flash('success', 'Welcome to Lekhya! Your 14-day free trial has started.');
        }

        return response()->json(['redirect' => route('dashboard')]);
    }
}
