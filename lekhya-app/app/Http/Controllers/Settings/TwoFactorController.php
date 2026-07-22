<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Auth\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    public function __construct(private TotpService $totp) {}

    public function show(Request $request)
    {
        $user = $request->user();

        // While enrolling (secret set but unconfirmed) show the QR to scan.
        $qrUri = null;
        if ($user->two_factor_secret && ! $user->two_factor_confirmed_at) {
            $qrUri = $this->totp->provisioningUri(
                $user->two_factor_secret,
                $user->email,
                config('app.name', 'Lekhya')
            );
        }

        $enrolling = (bool) ($user->two_factor_secret && ! $user->two_factor_confirmed_at);
        // Codes are shown during setup, and once more right after a regenerate.
        $showRecovery = $enrolling || $request->session()->get('show_recovery');

        return view('settings.security', [
            'user'           => $user,
            'enabled'        => $user->hasTwoFactorEnabled(),
            'enrolling'      => $enrolling,
            'qrUri'          => $qrUri,
            'secret'         => $user->two_factor_secret,
            'recoveryCodes'  => $showRecovery ? $user->two_factor_recovery_codes : null,
        ]);
    }

    /** Begin enrolment: mint a fresh secret + recovery codes (unconfirmed). */
    public function enable(Request $request)
    {
        $user = $request->user();

        $user->forceFill([
            'two_factor_secret'         => $this->totp->generateSecret(),
            'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
            'two_factor_confirmed_at'   => null,
        ])->save();

        return redirect()->route('settings.security')
            ->with('info', 'Scan the QR code with your authenticator app, then enter the 6-digit code to finish.');
    }

    /** Finish enrolment by verifying the first code from the app. */
    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $user = $request->user();

        abort_unless($user->two_factor_secret, 400, 'Start two-factor setup first.');

        if (! $this->totp->verify($user->two_factor_secret, $request->code)) {
            return back()->withErrors(['code' => 'That code is incorrect or expired. Try the current code from your app.']);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return redirect()->route('settings.security')
            ->with('success', 'Two-factor authentication is now on. Keep your recovery codes somewhere safe.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();

        return redirect()->route('settings.security')->with('success', 'Two-factor authentication turned off.');
    }

    /** Issue a fresh set of recovery codes (invalidates the old ones). */
    public function regenerateRecoveryCodes(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasTwoFactorEnabled(), 400);

        $user->forceFill(['two_factor_recovery_codes' => $this->generateRecoveryCodes()])->save();

        return back()->with(['success' => 'New recovery codes generated.', 'show_recovery' => true]);
    }

    private function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(4) . '-' . Str::random(4)))
            ->all();
    }
}
