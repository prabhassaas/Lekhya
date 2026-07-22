<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsSeeder;
use App\Services\Accounting\ReceiptPaymentService;
use App\Services\Notification\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private function user(int $tenantId, string $email): User
    {
        return User::create(['tenant_id' => $tenantId, 'name' => 'U', 'email' => $email, 'password' => Hash::make('x'), 'is_active' => true]);
    }

    public function test_notifier_reaches_all_tenant_members_except_the_actor(): void
    {
        $tenant = Tenant::create(['name' => 'Co', 'slug' => 'co']);
        $actor  = $this->user($tenant->id, 'actor@t.co');
        $other  = $this->user($tenant->id, 'other@t.co');
        $outsider = $this->user(Tenant::create(['name' => 'X', 'slug' => 'x'])->id, 'out@t.co');

        app(Notifier::class)->toTenant($tenant->id, 'Hello', 'body', '/somewhere', 'fa-bell', 'navy', $actor->id);

        $this->assertSame(0, $actor->fresh()->notifications()->count(), 'actor is excluded');
        $this->assertSame(1, $other->fresh()->notifications()->count());
        $this->assertSame(0, $outsider->fresh()->notifications()->count(), 'other tenants never see it');
        $this->assertSame('Hello', $other->fresh()->notifications()->first()->data['title']);
    }

    public function test_open_marks_read_and_redirects_to_deep_link(): void
    {
        $tenant = Tenant::create(['name' => 'Co', 'slug' => 'co2']);
        $user = $this->user($tenant->id, 'u@t.co');
        app(Notifier::class)->toUser($user, 'Ping', 'x', '/accounting/reports');
        $n = $user->notifications()->first();

        $this->actingAs($user)->get(route('notifications.open', $n->id))->assertRedirect('/accounting/reports');
        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_mark_all_read_clears_the_unread_count(): void
    {
        $tenant = Tenant::create(['name' => 'Co', 'slug' => 'co3']);
        $user = $this->user($tenant->id, 'u3@t.co');
        app(Notifier::class)->toUser($user, 'A');
        app(Notifier::class)->toUser($user, 'B');
        $this->assertSame(2, $user->unreadNotifications()->count());

        $this->actingAs($user)->post(route('notifications.read_all'))->assertRedirect();
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_recording_a_receipt_notifies_other_team_members(): void
    {
        $tenant = Tenant::create(['name' => 'Co', 'slug' => 'co4']);
        $actor  = $this->user($tenant->id, 'a4@t.co');
        $mate   = $this->user($tenant->id, 'm4@t.co');
        $fy = FiscalYear::create(['tenant_id' => $tenant->id, 'name' => 'FY', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true]);
        app(ChartOfAccountsSeeder::class)->seed($tenant->id);
        $cust = Party::create(['tenant_id' => $tenant->id, 'type' => 'customer', 'name' => 'Cust', 'is_active' => true]);
        $bank = Account::where('tenant_id', $tenant->id)->where('code', '1300')->first();
        $inv = Invoice::create([
            'tenant_id' => $tenant->id, 'fiscal_year_id' => $fy->id, 'type' => 'sales', 'invoice_number' => 'SI-1',
            'invoice_date' => '2026-07-01', 'party_id' => $cust->id, 'status' => 'posted',
            'taxable_amount' => 5000, 'total_amount' => 5000, 'balance_amount' => 5000, 'created_by' => $actor->id,
        ]);

        app(ReceiptPaymentService::class)->record([
            'tenant_id' => $tenant->id, 'type' => 'receipt', 'party_id' => $cust->id,
            'ledger_account_id' => $bank->id, 'payment_date' => '2026-07-05',
            'allocations' => [['invoice_id' => $inv->id, 'amount' => 5000]], 'created_by' => $actor->id,
        ]);

        $this->assertSame(1, $mate->fresh()->notifications()->count());
        $this->assertSame(0, $actor->fresh()->notifications()->count(), 'the person who recorded it is not re-notified');
    }
}
