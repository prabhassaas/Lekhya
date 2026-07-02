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

class SsoController extends Controller
{
    public function handle(Request $request)
    {
        $token = $request->get('token');

        if (! $token) {
            return redirect()->route('login')
                ->withErrors(['error' => 'SSO session invalid. Please log in again.']);
        }

        $secret  = config('services.prabhas.sso_secret');
        $payload = $this->parseJwt($token, (string) $secret);

        if (! $payload) {
            return redirect()->route('login')
                ->withErrors(['error' => 'SSO session invalid. Please log in again.']);
        }

        // Check token expiry
        if (! isset($payload['exp']) || $payload['exp'] < time()) {
            return redirect()->route('login')
                ->withErrors(['error' => 'SSO session invalid. Please log in again.']);
        }

        // Extract standard claims
        $email  = $payload['email'] ?? null;
        $name   = $payload['name']  ?? null;
        $phone  = $payload['phone'] ?? null;
        $plan   = $payload['plan']  ?? 'practice';

        if (! $email) {
            return redirect()->route('login')
                ->withErrors(['error' => 'SSO session invalid. Please log in again.']);
        }

        try {
            $user = DB::transaction(function () use ($email, $name, $phone, $plan) {
                $user = User::where('email', $email)->first();

                if (! $user) {
                    // New user — provision tenant, entitlement, fiscal year, CoA, and user
                    $displayName = $name ?? $email;

                    $tenant = Tenant::create([
                        'name'  => $displayName,
                        'slug'  => Str::slug($displayName) . '-' . Str::random(4),
                        'email' => $email,
                        'phone' => $phone,
                    ]);

                    Entitlement::create([
                        'tenant_id'         => $tenant->id,
                        'app'               => 'lekhya',
                        'edition'           => 'standard',
                        'plan'              => $plan,
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
                        'tenant_id'     => $tenant->id,
                        'name'          => $displayName,
                        'email'         => $email,
                        'phone'         => $phone,
                        'password'      => Hash::make(Str::random(32)),
                        'is_active'     => true,
                        'last_login_at' => now(),
                    ]);

                    $user->assignRole('owner');

                    app(\App\Services\Accounting\ChartOfAccountsSeeder::class)->seed($tenant->id);
                } else {
                    // Existing user — sync profile fields and stamp last login
                    $updates = ['last_login_at' => now()];

                    if ($name && $user->name !== $name) {
                        $updates['name'] = $name;
                    }
                    if ($phone && $user->phone !== $phone) {
                        $updates['phone'] = $phone;
                    }

                    $user->update($updates);
                }

                return $user;
            });

            Auth::login($user);
            $request->session()->regenerate();

            // Only allow relative redirect paths to prevent open redirects
            $redirectTo = $request->get('redirect_to', '/dashboard');
            if (! Str::startsWith($redirectTo, '/') || Str::startsWith($redirectTo, '//')) {
                $redirectTo = '/dashboard';
            }

            return redirect($redirectTo);

        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')
                ->withErrors(['error' => 'SSO session invalid. Please log in again.']);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(config('services.prabhas.logout_url', 'https://accounts.prabhas.in/logout'));
    }

    /**
     * Manually parse and verify an HS256 JWT without a library dependency.
     * Returns the decoded payload array on success, or null if the token is
     * malformed, uses the wrong algorithm, or has an invalid signature.
     */
    private function parseJwt(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        // Verify header declares HS256 — reject anything else
        $headerJson = $this->base64UrlDecode($encodedHeader);
        if ($headerJson === false) {
            return null;
        }
        $header = json_decode($headerJson, true);
        if (! is_array($header) || ($header['alg'] ?? '') !== 'HS256') {
            return null;
        }

        // Constant-time signature verification
        $signingInput = $encodedHeader . '.' . $encodedPayload;
        $expectedSig  = hash_hmac('sha256', $signingInput, $secret, true);
        $actualSig    = $this->base64UrlDecode($encodedSignature);

        if ($actualSig === false || ! hash_equals($expectedSig, $actualSig)) {
            return null;
        }

        // Decode payload
        $payloadJson = $this->base64UrlDecode($encodedPayload);
        if ($payloadJson === false) {
            return null;
        }

        $payload = json_decode($payloadJson, true);

        return is_array($payload) ? $payload : null;
    }

    /** Convert base64url encoding to standard base64 and decode. */
    private function base64UrlDecode(string $input): string|false
    {
        $remainder = strlen($input) % 4;
        if ($remainder !== 0) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/'), strict: true);
    }
}
