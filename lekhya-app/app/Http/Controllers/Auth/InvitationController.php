<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class InvitationController extends Controller
{
    /** Invitations are valid for 7 days from when they were sent. */
    private const EXPIRY_DAYS = 7;

    public function show(string $token)
    {
        $user = $this->resolve($token);

        return view('auth.accept-invitation', [
            'token'   => $token,
            'user'    => $user,
            'company' => $user->tenant?->name,
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $user = $this->resolve($token);

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->forceFill([
            'password'          => Hash::make($request->password),
            'invitation_token'  => null,
            'is_active'         => true,
            'email_verified_at' => now(),
        ])->save();

        if ($user->invited_by) {
            app(\App\Services\Notification\Notifier::class)->toUser(
                User::find($user->invited_by),
                "{$user->name} joined the team",
                "{$user->name} accepted your invitation and is now active.",
                route('settings.users'),
                'fa-user-check',
                'green',
            );
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome aboard! Your account is ready.');
    }

    /** Find a pending invite by its raw token, or 404 if missing/expired. */
    private function resolve(string $token): User
    {
        $user = User::where('invitation_token', hash('sha256', $token))->first();

        abort_if(! $user, 404, 'This invitation link is invalid or has already been used.');
        abort_if(
            $user->invited_at && $user->invited_at->addDays(self::EXPIRY_DAYS)->isPast(),
            403,
            'This invitation link has expired. Ask an admin to invite you again.'
        );

        return $user;
    }
}
