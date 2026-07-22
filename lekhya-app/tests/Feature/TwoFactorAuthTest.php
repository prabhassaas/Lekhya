<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Auth\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    private function user(bool $with2fa, string $slug): array
    {
        $tenant = Tenant::create(['name' => 'T ' . $slug, 'slug' => $slug]);
        $secret = app(TotpService::class)->generateSecret();
        $user = User::create([
            'tenant_id' => $tenant->id, 'name' => 'U', 'email' => "u-{$slug}@t.co",
            'password' => Hash::make('secret-pass'), 'is_active' => true,
        ]);
        if ($with2fa) {
            $user->forceFill([
                'two_factor_secret' => $secret,
                'two_factor_recovery_codes' => ['AAAA-BBBB', 'CCCC-DDDD'],
                'two_factor_confirmed_at' => now(),
            ])->save();
        }

        return [$user, $secret];
    }

    public function test_login_without_2fa_goes_straight_to_dashboard(): void
    {
        [$user] = $this->user(false, 'a');

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_2fa_diverts_to_challenge_then_completes(): void
    {
        [$user, $secret] = $this->user(true, 'b');

        // Correct password alone must NOT log them in — it diverts to the challenge.
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertRedirect(route('two-factor.login'));
        $this->assertGuest();

        // Correct TOTP code finishes the login.
        $code = app(TotpService::class)->codeAt($secret);
        $this->post('/two-factor', ['code' => $code])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_wrong_code_keeps_user_out(): void
    {
        [$user] = $this->user(true, 'c');
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);

        $this->post('/two-factor', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_completes_and_is_consumed(): void
    {
        [$user] = $this->user(true, 'd');
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);

        $this->post('/two-factor', ['code' => 'AAAA-BBBB'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());

        // The used recovery code is burned; the other remains.
        $remaining = $user->fresh()->two_factor_recovery_codes;
        $this->assertNotContains('AAAA-BBBB', $remaining);
        $this->assertContains('CCCC-DDDD', $remaining);
    }

    public function test_enable_then_confirm_turns_on_2fa(): void
    {
        [$user] = $this->user(false, 'e');

        $this->actingAs($user)->post(route('settings.security.2fa.enable'))->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at); // not yet confirmed

        $code = app(TotpService::class)->codeAt($user->two_factor_secret);
        $this->actingAs($user)->post(route('settings.security.2fa.confirm'), ['code' => $code])->assertRedirect();
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }
}
