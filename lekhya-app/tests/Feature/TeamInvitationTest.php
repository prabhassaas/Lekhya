<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    private function pendingInvite(string $raw, string $slug, ?string $invitedAt = 'now'): User
    {
        $tenant = Tenant::create(['name' => 'Co ' . $slug, 'slug' => $slug]);

        return User::create([
            'tenant_id'        => $tenant->id,
            'name'             => 'Invitee',
            'email'            => "invitee-{$slug}@t.co",
            'password'         => Hash::make(\Illuminate\Support\Str::random(40)),
            'is_active'        => true,
            'invitation_token' => hash('sha256', $raw),
            'invited_at'       => $invitedAt === 'now' ? now() : $invitedAt,
        ]);
    }

    public function test_accept_sets_password_clears_token_and_logs_in(): void
    {
        $user = $this->pendingInvite('rawtoken-a', 'a');

        $this->get('/invitation/rawtoken-a')->assertOk();

        $this->post('/invitation/rawtoken-a', [
            'password' => 'my-new-pass-8',
            'password_confirmation' => 'my-new-pass-8',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertNull($user->invitation_token);
        $this->assertTrue(Hash::check('my-new-pass-8', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_invitation_is_rejected(): void
    {
        $this->pendingInvite('rawtoken-b', 'b', now()->subDays(8));

        $this->get('/invitation/rawtoken-b')->assertStatus(403);
    }

    public function test_unknown_token_is_404(): void
    {
        $this->get('/invitation/does-not-exist')->assertStatus(404);
    }

    public function test_password_confirmation_is_required(): void
    {
        $this->pendingInvite('rawtoken-c', 'c');

        $this->post('/invitation/rawtoken-c', [
            'password' => 'my-new-pass-8',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }
}
