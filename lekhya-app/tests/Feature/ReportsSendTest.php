<?php

namespace Tests\Feature;

use App\Mail\ReportMail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportsSendTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, string $slug): User
    {
        Role::findOrCreate($role, 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::create(['name' => 'Co ' . $slug, 'slug' => $slug, 'gstin' => '29ABCDE1234F1Z5']);
        $user = User::create([
            'tenant_id' => $tenant->id, 'name' => 'U', 'email' => "u-{$slug}@t.co",
            'password' => Hash::make('x'), 'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_pdf_export_now_works_for_a_previously_unsupported_report(): void
    {
        $user = $this->userWithRole('owner', 'a');

        $res = $this->actingAs($user)->get(route('accounting.reports.pdf', 'day-book'));
        $res->assertOk();
        $this->assertStringContainsString('application/pdf', strtolower($res->headers->get('content-type')));
    }

    public function test_owner_can_email_a_report(): void
    {
        Mail::fake();
        $user = $this->userWithRole('owner', 'b');

        $this->actingAs($user)
            ->post(route('accounting.reports.send', 'gst-summary'), [
                'channel' => 'email', 'recipient' => 'client@acme.co', 'message' => 'Your GST summary.',
            ])
            ->assertRedirect();

        Mail::assertSent(ReportMail::class, fn ($m) => $m->hasTo('client@acme.co'));
    }

    public function test_viewer_cannot_send_reports(): void
    {
        Mail::fake();
        $user = $this->userWithRole('viewer', 'c');

        $this->actingAs($user)
            ->post(route('accounting.reports.send', 'gst-summary'), ['channel' => 'email', 'to' => 'x@y.co'])
            ->assertStatus(403);

        Mail::assertNothingSent();
    }

    public function test_email_requires_a_recipient(): void
    {
        $user = $this->userWithRole('owner', 'd');

        $this->actingAs($user)
            ->post(route('accounting.reports.send', 'day-book'), ['channel' => 'email'])
            ->assertSessionHasErrors('recipient');
    }

    public function test_unknown_report_type_is_404(): void
    {
        $user = $this->userWithRole('owner', 'e');
        $this->actingAs($user)->get(route('accounting.reports.pdf', 'not-a-report'))->assertStatus(404);
    }
}
