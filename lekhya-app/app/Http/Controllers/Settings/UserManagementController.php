<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        $tempPassword = Str::random(16);

        $user = User::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($tempPassword),
            'is_active' => true,
        ]);

        $user->assignRole($data['role']);

        // TODO: replace with a real mailable once the mail driver is wired up
        Log::info('User invited to workspace', [
            'tenant_id'     => auth()->user()->tenant_id,
            'invited_by'    => auth()->user()->email,
            'new_user'      => $data['email'],
            'role'          => $data['role'],
            'temp_password' => $tempPassword,
        ]);

        return redirect()->route('settings.users')
            ->with('success', "Invitation created for {$data['email']}. Share their temporary credentials securely.");
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
