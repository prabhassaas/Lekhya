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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReceiptPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function scaffold(string $slug): array
    {
        $tenant = Tenant::create(['name' => 'Test ' . $slug, 'slug' => $slug]);
        $user   = User::create(['tenant_id' => $tenant->id, 'name' => 'U', 'email' => "u-{$slug}@test.co", 'password' => bcrypt('x')]);
        $fy     = FiscalYear::create(['tenant_id' => $tenant->id, 'name' => 'FY26', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true]);
        app(ChartOfAccountsSeeder::class)->seed($tenant->id);
        $cust = Party::create(['tenant_id' => $tenant->id, 'type' => 'customer', 'name' => 'Cust', 'is_active' => true]);
        $cash = Account::where('tenant_id', $tenant->id)->where('code', '1300')->first();

        return compact('tenant', 'user', 'fy', 'cust', 'cash');
    }

    private function postedSalesInvoice(array $ctx, float $total): Invoice
    {
        return Invoice::create([
            'tenant_id' => $ctx['tenant']->id, 'fiscal_year_id' => $ctx['fy']->id, 'type' => 'sales',
            'invoice_number' => 'INV-' . uniqid(), 'invoice_date' => '2026-07-01', 'party_id' => $ctx['cust']->id,
            'status' => 'posted', 'taxable_amount' => $total, 'total_amount' => $total,
            'paid_amount' => 0, 'balance_amount' => $total, 'created_by' => $ctx['user']->id,
        ]);
    }

    public function test_receipt_with_tds_clears_invoice_and_posts_balanced_voucher(): void
    {
        $ctx = $this->scaffold('a');
        $inv = $this->postedSalesInvoice($ctx, 11800);

        $payment = app(ReceiptPaymentService::class)->record([
            'tenant_id' => $ctx['tenant']->id, 'type' => 'receipt', 'party_id' => $ctx['cust']->id,
            'ledger_account_id' => $ctx['cash']->id, 'payment_date' => '2026-07-05', 'tds_amount' => 1180,
            'allocations' => [['invoice_id' => $inv->id, 'amount' => 11800]], 'created_by' => $ctx['user']->id,
        ]);

        $inv->refresh();
        $this->assertSame('paid', $inv->status);
        $this->assertEqualsWithDelta(0.0, (float) $inv->balance_amount, 0.001);
        $this->assertEqualsWithDelta(11800.0, (float) $inv->paid_amount, 0.001);

        $journal = $payment->journal;
        $this->assertEqualsWithDelta((float) $journal->total_debit, (float) $journal->total_credit, 0.001, 'journal must balance');
        $this->assertEqualsWithDelta(11800.0, (float) $journal->total_debit, 0.001);

        // Cash actually received = gross − TDS.
        $cashLine = $journal->lines->firstWhere('account_id', $ctx['cash']->id);
        $this->assertEqualsWithDelta(10620.0, (float) $cashLine->debit, 0.001);
    }

    public function test_partial_receipt_marks_invoice_partially_paid(): void
    {
        $ctx = $this->scaffold('b');
        $inv = $this->postedSalesInvoice($ctx, 10000);

        app(ReceiptPaymentService::class)->record([
            'tenant_id' => $ctx['tenant']->id, 'type' => 'receipt', 'party_id' => $ctx['cust']->id,
            'ledger_account_id' => $ctx['cash']->id, 'payment_date' => '2026-07-05',
            'allocations' => [['invoice_id' => $inv->id, 'amount' => 4000]], 'created_by' => $ctx['user']->id,
        ]);

        $inv->refresh();
        $this->assertSame('partially_paid', $inv->status);
        $this->assertEqualsWithDelta(6000.0, (float) $inv->balance_amount, 0.001);
    }

    public function test_over_allocation_is_rejected(): void
    {
        $ctx = $this->scaffold('c');
        $inv = $this->postedSalesInvoice($ctx, 1000);

        $this->expectException(ValidationException::class);
        app(ReceiptPaymentService::class)->record([
            'tenant_id' => $ctx['tenant']->id, 'type' => 'receipt', 'party_id' => $ctx['cust']->id,
            'ledger_account_id' => $ctx['cash']->id, 'payment_date' => '2026-07-05',
            'allocations' => [['invoice_id' => $inv->id, 'amount' => 5000]], 'created_by' => $ctx['user']->id,
        ]);
    }
}
