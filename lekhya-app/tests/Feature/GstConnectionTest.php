<?php

namespace Tests\Feature;

use App\Models\Entitlement;
use App\Models\FiscalYear;
use App\Models\GstFiling;
use App\Models\GstSetting;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GstConnectionTest extends TestCase
{
    use RefreshDatabase;

    private function scaffold(string $slug, bool $entitled = true): array
    {
        $tenant = Tenant::create(['name' => 'Co ' . $slug, 'slug' => $slug, 'gstin' => '29ABCDE1234F1Z5']);
        if ($entitled) {
            Entitlement::create([
                'tenant_id' => $tenant->id, 'app' => 'lekhya', 'edition' => 'standard', 'plan' => 'practice',
                'client_seat_limit' => 10, 'is_active' => true, 'trial_ends_at' => now()->addDays(14),
            ]);
        }
        $user = User::create(['tenant_id' => $tenant->id, 'name' => 'U', 'email' => "u-{$slug}@t.co", 'password' => Hash::make('x'), 'is_active' => true]);

        return compact('tenant', 'user');
    }

    private function connect(Tenant $tenant): GstSetting
    {
        return GstSetting::create([
            'tenant_id' => $tenant->id, 'gstin' => '29ABCDE1234F1Z5', 'environment' => 'sandbox',
            'einvoice_username' => 'apiuser', 'einvoice_password' => 'secret-pass',
            'status' => 'connected', 'connected_at' => now(),
        ]);
    }

    private function postedInvoice(array $ctx): Invoice
    {
        $fy = FiscalYear::create(['tenant_id' => $ctx['tenant']->id, 'name' => 'FY', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true]);
        $party = Party::create(['tenant_id' => $ctx['tenant']->id, 'type' => 'customer', 'name' => 'C', 'is_active' => true]);

        return Invoice::create([
            'tenant_id' => $ctx['tenant']->id, 'fiscal_year_id' => $fy->id, 'type' => 'sales',
            'invoice_number' => 'SI-' . uniqid(), 'invoice_date' => '2026-07-01', 'party_id' => $party->id,
            'status' => 'posted', 'total_amount' => 1000, 'taxable_amount' => 1000, 'balance_amount' => 1000,
            'created_by' => $ctx['user']->id,
        ]);
    }

    public function test_credentials_are_stored_encrypted_and_connect_flips_status(): void
    {
        $ctx = $this->scaffold('a');

        $this->actingAs($ctx['user'])->put(route('settings.gst.update'), [
            'gstin' => '29ABCDE1234F1Z5', 'environment' => 'sandbox',
            'einvoice_username' => 'apiuser', 'einvoice_password' => 'topsecret',
        ])->assertRedirect(route('settings.gst'));

        // At rest it is ciphertext, not the plaintext password.
        $raw = DB::table('gst_settings')->where('tenant_id', $ctx['tenant']->id)->value('einvoice_password');
        $this->assertNotNull($raw);
        $this->assertStringNotContainsString('topsecret', $raw);

        $setting = GstSetting::where('tenant_id', $ctx['tenant']->id)->first();
        $this->assertSame('topsecret', $setting->einvoice_password); // decrypts through the cast
        $this->assertSame('connected', $setting->status);
        $this->assertTrue($ctx['tenant']->fresh()->gstConnected());
    }

    public function test_filing_is_gated_by_plan(): void
    {
        $ctx = $this->scaffold('b', entitled: false);

        $this->actingAs($ctx['user'])->put(route('settings.gst.update'), [
            'gstin' => '29ABCDE1234F1Z5', 'environment' => 'sandbox',
            'einvoice_username' => 'x', 'einvoice_password' => 'y',
        ])->assertStatus(403);
    }

    public function test_generate_irn_is_blocked_until_gst_is_connected(): void
    {
        $ctx = $this->scaffold('c'); // entitled but NOT connected
        $inv = $this->postedInvoice($ctx);

        $this->actingAs($ctx['user'])
            ->post(route('gst.einvoice.generate', $inv))
            ->assertRedirect(route('settings.gst'));

        $this->assertNull($inv->fresh()->irn);
        $this->assertSame(0, GstFiling::where('tenant_id', $ctx['tenant']->id)->count());
    }

    public function test_generate_irn_works_and_is_metered_once_connected(): void
    {
        $ctx = $this->scaffold('d');
        $this->connect($ctx['tenant']);
        $inv = $this->postedInvoice($ctx);

        $this->actingAs($ctx['user'])->post(route('gst.einvoice.generate', $inv))->assertRedirect();

        $this->assertNotNull($inv->fresh()->irn);
        $filing = GstFiling::where('tenant_id', $ctx['tenant']->id)->where('type', 'irn')->first();
        $this->assertNotNull($filing);
        $this->assertSame('success', $filing->status);
        $this->assertSame(1, $ctx['tenant']->fresh()->gstFilingsUsed());
    }

    public function test_a_tenant_cannot_load_or_touch_another_tenants_connection(): void
    {
        $a = $this->scaffold('t1');
        $b = $this->scaffold('t2');
        $this->connect($a['tenant']);

        // Acting as tenant B, the settings screen resolves B's own (empty) connection, never A's.
        $this->actingAs($b['user'])->get(route('settings.gst'))->assertOk();
        $this->assertFalse($b['tenant']->fresh()->gstConnected());
        $this->assertTrue($a['tenant']->fresh()->gstConnected());
    }
}
