<?php

namespace Tests\Feature;

use App\Models\FiscalYear;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Party;
use App\Models\RecurringInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsSeeder;
use App\Services\Accounting\RecurringInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function scaffold(string $slug): array
    {
        $tenant = Tenant::create(['name' => 'Test ' . $slug, 'slug' => $slug]);
        $user   = User::create(['tenant_id' => $tenant->id, 'name' => 'U', 'email' => "u-{$slug}@test.co", 'password' => bcrypt('x'), 'is_active' => true]);
        $fy     = FiscalYear::create(['tenant_id' => $tenant->id, 'name' => 'FY26', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true]);
        $cust   = Party::create(['tenant_id' => $tenant->id, 'type' => 'customer', 'name' => 'Acme', 'is_active' => true]);

        return compact('tenant', 'user', 'fy', 'cust');
    }

    private function sourceInvoice(array $ctx): Invoice
    {
        $inv = Invoice::create([
            'tenant_id' => $ctx['tenant']->id, 'fiscal_year_id' => $ctx['fy']->id, 'type' => 'sales',
            'document_type' => 'tax_invoice', 'invoice_number' => 'SI/26/0001', 'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-15', 'party_id' => $ctx['cust']->id, 'status' => 'posted',
            'taxable_amount' => 10000, 'igst_amount' => 1800, 'total_tax' => 1800, 'total_amount' => 11800,
            'balance_amount' => 11800, 'created_by' => $ctx['user']->id,
        ]);
        InvoiceLine::create([
            'tenant_id' => $ctx['tenant']->id, 'invoice_id' => $inv->id, 'line_order' => 0,
            'description' => 'Monthly retainer', 'quantity' => 1, 'rate' => 10000, 'taxable_amount' => 10000,
            'igst_rate' => 18, 'igst_amount' => 1800, 'line_total' => 11800,
        ]);

        return $inv->load('lines');
    }

    public function test_generate_raises_draft_invoice_and_advances_schedule(): void
    {
        $ctx = $this->scaffold('a');
        $src = $this->sourceInvoice($ctx);

        $schedule = app(RecurringInvoiceService::class)->snapshotFrom($src, [
            'frequency' => 'monthly', 'start_date' => '2026-08-01', 'created_by' => $ctx['user']->id,
        ]);
        $this->assertSame('2026-08-01', $schedule->next_run_date->toDateString());

        $inv = app(RecurringInvoiceService::class)->generate($schedule);

        $this->assertNotNull($inv);
        $this->assertSame('draft', $inv->status);
        $this->assertSame('recurring', $inv->source);
        $this->assertSame($schedule->id, $inv->recurring_invoice_id);
        $this->assertSame('2026-08-01', $inv->invoice_date->toDateString());
        // due_date preserves the original 14-day offset (Jul 1 → Jul 15).
        $this->assertSame('2026-08-15', $inv->due_date->toDateString());
        $this->assertEqualsWithDelta(11800.0, (float) $inv->total_amount, 0.001);
        $this->assertEqualsWithDelta(11800.0, (float) $inv->balance_amount, 0.001);
        $this->assertCount(1, $inv->lines);
        $this->assertSame('Monthly retainer', $inv->lines->first()->description);

        $schedule->refresh();
        $this->assertSame(1, $schedule->occurrences_generated);
        $this->assertSame('2026-09-01', $schedule->next_run_date->toDateString());
        $this->assertSame($inv->id, $schedule->last_invoice_id);
    }

    public function test_schedule_ends_after_occurrence_limit(): void
    {
        $ctx = $this->scaffold('b');
        $src = $this->sourceInvoice($ctx);

        $schedule = app(RecurringInvoiceService::class)->snapshotFrom($src, [
            'frequency' => 'monthly', 'start_date' => '2026-08-01',
            'occurrences_limit' => 2, 'created_by' => $ctx['user']->id,
        ]);

        app(RecurringInvoiceService::class)->generate($schedule);
        $schedule->refresh();
        $this->assertSame('active', $schedule->status);

        app(RecurringInvoiceService::class)->generate($schedule);
        $schedule->refresh();
        $this->assertSame('ended', $schedule->status);
        $this->assertSame(2, $schedule->occurrences_generated);
        $this->assertFalse($schedule->isDue());
    }

    public function test_run_due_catches_up_all_missed_periods(): void
    {
        $ctx = $this->scaffold('c');
        $src = $this->sourceInvoice($ctx);

        // Start three months back; a single run should catch up every missed month.
        $schedule = app(RecurringInvoiceService::class)->snapshotFrom($src, [
            'frequency' => 'monthly', 'start_date' => now()->toDateString(), 'created_by' => $ctx['user']->id,
        ]);
        $schedule->update(['start_date' => now()->subMonths(3)->toDateString(), 'next_run_date' => now()->subMonths(3)->toDateString()]);

        $raised = app(RecurringInvoiceService::class)->runDue($ctx['tenant']->id);

        $this->assertGreaterThanOrEqual(3, $raised);
        $schedule->refresh();
        $this->assertTrue($schedule->next_run_date->isFuture());
    }

    public function test_auto_post_posts_generated_tax_invoice(): void
    {
        $ctx = $this->scaffold('d');
        app(ChartOfAccountsSeeder::class)->seed($ctx['tenant']->id);
        $src = $this->sourceInvoice($ctx);

        $schedule = app(RecurringInvoiceService::class)->snapshotFrom($src, [
            'frequency' => 'monthly', 'start_date' => now()->toDateString(),
            'auto_post' => true, 'created_by' => $ctx['user']->id,
        ]);

        $inv = app(RecurringInvoiceService::class)->generate($schedule);

        $this->assertSame('posted', $inv->fresh()->status);
        $this->assertNotNull($inv->fresh()->journal_id);
    }
}
