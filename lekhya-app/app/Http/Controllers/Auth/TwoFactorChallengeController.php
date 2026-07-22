<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorChallengeController extends Controller
{
    public const SESSION_ID = 'login.2fa.id';
    public const SESSION_REMEMBER = 'login.2fa.remember';

    public function __construct(private TotpService $totp) {}

    public function show(Request $request)
    {
        abort_unless($request->session()->has(self::SESSION_ID), 419, 'Your session expired — please sign in again.');

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $userId = $request->session()->get(self::SESSION_ID);
        abort_unless($userId, 419);

        $user = User::find($userId);
        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget([self::SESSION_ID, self::SESSION_REMEMBER]);
            return redirect()->route('login');
        }

        $code = trim($request->code);

        if ($this->totp->verify($user->two_factor_secret, $code) || $this->consumeRecoveryCode($user, $code)) {
            $remember = (bool) $request->session()->pull(self::SESSION_REMEMBER, false);
            $request->session()->forget(self::SESSION_ID);

            Auth::login($user, $remember);
            $user->update(['last_login_at' => now()]);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['code' => 'Invalid code. Enter the current 6-digit code or a recovery code.']);
    }

    /** Match a recovery code (case-insensitive) and burn it if used. */
    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        foreach ($codes as $i => $stored) {
            if (hash_equals(strtoupper($stored), strtoupper($code))) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                return true;
            }
        }

        return false;
    }
}
