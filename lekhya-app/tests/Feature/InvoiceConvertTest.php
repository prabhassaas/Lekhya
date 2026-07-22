<?php

namespace Tests\Feature;

use App\Models\FiscalYear;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Party;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceConvertTest extends TestCase
{
    use RefreshDatabase;

    private function scaffold(string $slug): array
    {
        $tenant = Tenant::create(['name' => 'Test ' . $slug, 'slug' => $slug]);
        $user   = User::create(['tenant_id' => $tenant->id, 'name' => 'U', 'email' => "u-{$slug}@test.co", 'password' => bcrypt('x'), 'is_active' => true]);
        $fy     = FiscalYear::create(['tenant_id' => $tenant->id, 'name' => 'FY26', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true]);
        $cust   = Party::create(['tenant_id' => $tenant->id, 'type' => 'customer', 'name' => 'Cust', 'is_active' => true]);

        return compact('tenant', 'user', 'fy', 'cust');
    }

    private function quotation(array $ctx): Invoice
    {
        $inv = Invoice::create([
            'tenant_id' => $ctx['tenant']->id, 'fiscal_year_id' => $ctx['fy']->id, 'type' => 'sales',
            'document_type' => 'quotation', 'invoice_number' => 'QT/26/0001', 'invoice_date' => '2026-07-01',
            'party_id' => $ctx['cust']->id, 'status' => 'draft', 'taxable_amount' => 10000, 'total_amount' => 11800,
            'balance_amount' => 0, 'created_by' => $ctx['user']->id,
        ]);
        InvoiceLine::create([
            'tenant_id' => $ctx['tenant']->id, 'invoice_id' => $inv->id, 'line_order' => 0,
            'description' => 'Consulting', 'quantity' => 1, 'rate' => 10000, 'taxable_amount' => 10000,
            'igst_rate' => 18, 'igst_amount' => 1800, 'line_total' => 11800,
        ]);

        return $inv;
    }

    public function test_quotation_converts_to_sales_order_copying_lines(): void
    {
        $ctx = $this->scaffold('a');
        $quote = $this->quotation($ctx);

        $this->actingAs($ctx['user'])
            ->post(route('accounting.invoices.convert', $quote), ['document_type' => 'sales_order'])
            ->assertRedirect();

        $order = Invoice::where('tenant_id', $ctx['tenant']->id)->where('document_type', 'sales_order')->first();
        $this->assertNotNull($order);
        $this->assertSame($quote->id, $order->converted_from_id);
        $this->assertSame('draft', $order->status);
        $this->assertStringStartsWith('SO/', $order->invoice_number);
        $this->assertCount(1, $order->lines);
        $this->assertSame('Consulting', $order->lines->first()->description);
        // A non-invoice document carries no receivable balance.
        $this->assertEqualsWithDelta(0.0, (float) $order->balance_amount, 0.001);
    }

    public function test_quotation_converts_to_tax_invoice_with_receivable_balance(): void
    {
        $ctx = $this->scaffold('b');
        $quote = $this->quotation($ctx);

        $this->actingAs($ctx['user'])
            ->post(route('accounting.invoices.convert', $quote), ['document_type' => 'tax_invoice'])
            ->assertRedirect();

        $inv = Invoice::where('tenant_id', $ctx['tenant']->id)->where('document_type', 'tax_invoice')->first();
        $this->assertNotNull($inv);
        $this->assertStringStartsWith('SI/', $inv->invoice_number);
        // Tax invoice becomes a live receivable equal to its total.
        $this->assertEqualsWithDelta(11800.0, (float) $inv->balance_amount, 0.001);
    }

    public function test_disallowed_conversion_is_rejected(): void
    {
        $ctx = $this->scaffold('c');
        $quote = $this->quotation($ctx);

        // Quotation → Delivery Challan is not a defined step.
        $this->actingAs($ctx['user'])
            ->post(route('accounting.invoices.convert', $quote), ['document_type' => 'delivery_challan'])
            ->assertStatus(422);
    }

    public function test_cannot_convert_another_tenants_document(): void
    {
        $a = $this->scaffold('t1');
        $b = $this->scaffold('t2');
        $quote = $this->quotation($a);

        $this->actingAs($b['user'])
            ->post(route('accounting.invoices.convert', $quote), ['document_type' => 'sales_order'])
            ->assertStatus(403);
    }
}
