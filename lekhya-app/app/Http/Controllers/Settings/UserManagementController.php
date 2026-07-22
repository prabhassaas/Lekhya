<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    private function assertSameTenant(User $user): void
    {
        abort_if($user->tenant_id !== auth()->user()->tenant_id, 403, 'Access denied.');
    }

    public function index()
    {
        $tenant = auth()->user()->tenant;

        $users = User::with('roles', 'permissions')
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $roles = Role::orderBy('name')->get();

        $entitlement = $tenant->entitlements()
            ->where('app', 'lekhya')
            ->where('is_active', true)
            ->first();

        $seatLimit = $entitlement?->client_seat_limit ?? 5;
        $seatsUsed = $users->count();
        $isPramaan = $tenant->isPramaan();

        return view('settings.users', compact(
            'users', 'roles', 'entitlement', 'seatLimit', 'seatsUsed', 'isPramaan'
        ));
    }

    public function invite(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255|unique:users,email',
            'role'    => 'required|string|exists:roles,name',
            'message' => 'nullable|string|max:500',
        ]);

        $inviter = auth()->user();

        // The invitee sets their own password via a one-time signed link — we
        // never generate or transmit a password. Store only the token's hash.
        $rawToken = Str::random(48);

        $user = User::create([
            'tenant_id'        => $inviter->tenant_id,
            'name'             => $data['name'],
            'email'            => $data['email'],
            'password'         => Hash::make(Str::random(40)), // placeholder until they set one
            'is_active'        => true,
            'invitation_token' => hash('sha256', $rawToken),
            'invited_at'       => now(),
            'invited_by'       => $inviter->id,
        ]);

        $user->assignRole($data['role']);

        $acceptUrl = route('invitation.accept', $rawToken);
        $sent = true;
        try {
            Mail::to($user->email)->send(new \App\Mail\TeamInvitationMail(
                inviteeName: $user->name,
                companyName: $inviter->tenant?->name ?? config('app.name'),
                inviterName: $inviter->name,
                roleLabel: ucfirst($data['role']),
                acceptUrl: $acceptUrl,
                note: $data['message'] ?? null,
            ));
        } catch (\Throwable $e) {
            $sent = false;
            Log::error('Team invitation email failed', ['email' => $user->email, 'error' => $e->getMessage()]);
        }

        return redirect()->route('settings.users')->with(
            $sent ? 'success' : 'error',
            $sent
                ? "Invitation emailed to {$data['email']}. They'll set their own password from the link."
                : "{$data['name']} was added, but the invite email couldn't be sent. Share this setup link manually: {$acceptUrl}"
        );
    }

    /** Re-send an invitation (fresh token + email) for a still-pending user. */
    public function resendInvite(User $user)
    {
        $this->assertSameTenant($user);
        abort_unless($user->invitationPending(), 400, 'This user has already accepted their invitation.');

        $inviter = auth()->user();
        $rawToken = Str::random(48);
        $user->forceFill(['invitation_token' => hash('sha256', $rawToken), 'invited_at' => now()])->save();

        $acceptUrl = route('invitation.accept', $rawToken);
        try {
            Mail::to($user->email)->send(new \App\Mail\TeamInvitationMail(
                inviteeName: $user->name,
                companyName: $inviter->tenant?->name ?? config('app.name'),
                inviterName: $inviter->name,
                roleLabel: ucfirst(optional($user->roles->first())->name ?? 'member'),
                acceptUrl: $acceptUrl,
            ));
        } catch (\Throwable $e) {
            Log::error('Team invitation resend failed', ['email' => $user->email, 'error' => $e->getMessage()]);
            return back()->with('error', "Couldn't send the email. Share this link manually: {$acceptUrl}");
        }

        return back()->with('success', "Invitation re-sent to {$user->email}.");
    }

    public function updateRole(Request $request, User $user)
    {
        $this->assertSameTenant($user);

        $data = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->syncRoles([$data['role']]);

        return redirect()->route('settings.users')
            ->with('success', "{$user->name}'s role updated to " . ucfirst($data['role']) . '.');
    }

    public function updatePermissions(Request $request, User $user)
    {
        $this->assertSameTenant($user);

        $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $user->syncPermissions($request->input('permissions', []));

        return response()->json(['ok' => true]);
    }

    public function deactivate(User $user)
    {
        $this->assertSameTenant($user);
        abort_if($user->id === auth()->id(), 403, 'You cannot deactivate your own account.');

        $user->update(['is_active' => false]);

        return redirect()->route('settings.users')
            ->with('success', "{$user->name} has been deactivated.");
    }

    public function reactivate(User $user)
    {
        $this->assertSameTenant($user);

        $user->update(['is_active' => true]);

        return redirect()->route('settings.users')
            ->with('success', "{$user->name} has been reactivated.");
    }

    public function destroy(User $user)
    {
        $this->assertSameTenant($user);

        if ($user->id === auth()->id()) {
            return redirect()->route('settings.users')
                ->with('error', 'You cannot remove your own account.');
        }

        $ownerCount = User::where('tenant_id', auth()->user()->tenant_id)
            ->whereHas('roles', fn($q) => $q->where('name', 'owner'))
            ->count();

        if ($user->hasRole('owner') && $ownerCount <= 1) {
            return redirect()->route('settings.users')
                ->with('error', 'Cannot remove the last owner of this workspace.');
        }

        $user->delete();

        return redirect()->route('settings.users')
            ->with('success', "{$user->name} has been removed from this workspace.");
    }
}
