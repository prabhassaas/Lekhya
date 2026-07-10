<?php

namespace Database\Seeders;

use App\Models\FiscalYear;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsSeeder;
use App\Services\Accounting\InvoicePostingService;
use App\Services\GST\GstRateEngine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Marketing-screenshot demo data only — never run against production.
 * Creates one tenant with realistic Indian-business invoices, posted
 * through the real InvoicePostingService/GstRateEngine so every screen
 * (dashboard, GSTR-1) shows genuine computed numbers, not fixtures.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent — safe to run on every deploy. Skip if already seeded.
        if (Tenant::where('slug', 'suvarna-textiles-demo')->exists()) {
            $this->command?->info('Demo tenant already present — skipping DemoDataSeeder.');
            return;
        }

        $tenant = Tenant::create([
            'name'        => 'Suvarna Textiles Pvt Ltd',
            'slug'        => 'suvarna-textiles-demo',
            'gstin'       => '29AABCS1429B1ZP',
            'pan'         => 'AABCS1429B',
            'email'       => 'demo@lekhya.app',
            'phone'       => '+91 98450 12345',
            'address'     => '14, Residency Road',
            'city'        => 'Bengaluru',
            'state'       => 'Karnataka',
            'state_code'  => '29',
            'pincode'     => '560025',
            'country'     => 'India',
            'fiscal_year_start' => 'April',
            'currency'    => 'INR',
            'is_active'   => true,
        ]);

        FiscalYear::create([
            'tenant_id'  => $tenant->id,
            'name'       => date('Y') . '-' . substr(date('Y', strtotime('+1 year')), -2),
            'start_date' => date('Y') . '-04-01',
            'end_date'   => date('Y', strtotime('+1 year')) . '-03-31',
            'is_current' => true,
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Ananya Rao',
            'email'     => 'demo@lekhya.app',
            'phone'     => '+91 98450 12345',
            'password'  => bcrypt('demo12345'),
            'is_active' => true,
        ]);
        $user->assignRole('owner');

        app(ChartOfAccountsSeeder::class)->seed($tenant->id);

        $parties = [
            Party::create(['tenant_id' => $tenant->id, 'type' => 'customer', 'name' => 'Karnataka Weaves Retail', 'display_name' => 'Karnataka Weaves Retail', 'gstin' => '29AAGCK4837Q1Z6', 'state' => 'Karnataka', 'state_code' => '29', 'city' => 'Bengaluru', 'is_active' => true]),
            Party::create(['tenant_id' => $tenant->id, 'type' => 'customer', 'name' => 'Deccan Apparel Traders', 'display_name' => 'Deccan Apparel Traders', 'gstin' => '29AAFCD2210K1ZL', 'state' => 'Karnataka', 'state_code' => '29', 'city' => 'Mysuru', 'is_active' => true]),
            Party::create(['tenant_id' => $tenant->id, 'type' => 'customer', 'name' => 'Mumbai Fashion House', 'display_name' => 'Mumbai Fashion House', 'gstin' => '27AACCM5544P1ZQ', 'state' => 'Maharashtra', 'state_code' => '27', 'city' => 'Mumbai', 'is_active' => true]),
            Party::create(['tenant_id' => $tenant->id, 'type' => 'customer', 'name' => 'Walk-in Customer', 'display_name' => 'Walk-in Customer', 'gstin' => null, 'state' => 'Karnataka', 'state_code' => '29', 'city' => 'Bengaluru', 'is_active' => true]),
        ];

        $rateEngine = app(GstRateEngine::class);
        $posting = app(InvoicePostingService::class);

        $fiscalYearId = FiscalYear::where('tenant_id', $tenant->id)->where('is_current', true)->value('id');

        $lineItems = [
            ['hsn' => '998311', 'desc' => 'Management consulting — Q1 review', 'qty' => 1, 'rate' => 45000],
            ['hsn' => '8471', 'desc' => 'Point-of-sale computer terminals (x4)', 'qty' => 4, 'rate' => 22000],
            ['hsn' => '6402', 'desc' => 'Woven cotton fabric — 500m bolt', 'qty' => 3, 'rate' => 18500],
            ['hsn' => '998315', 'desc' => 'Monthly bookkeeping retainer', 'qty' => 1, 'rate' => 12000],
            ['hsn' => '8517', 'desc' => 'Store communication handsets (x6)', 'qty' => 6, 'rate' => 6500],
        ];

        $invoiceNo = 1;
        foreach ($parties as $i => $party) {
            $count = $i === 3 ? 1 : 2; // fewer for the walk-in/B2C party
            for ($n = 0; $n < $count; $n++) {
                $item = $lineItems[($invoiceNo - 1) % count($lineItems)];
                $qty = $item['qty'];
                $rate = $item['rate'];
                $taxable = round($qty * $rate, 2);

                $rates = $rateEngine->getRates($item['hsn'], $tenant->state_code, $party->state_code ?? $tenant->state_code);
                $tax = $rateEngine->calculateTax($taxable, $rates);

                $invoice = Invoice::create([
                    'tenant_id'       => $tenant->id,
                    'fiscal_year_id'  => $fiscalYearId,
                    'type'            => 'sales',
                    'invoice_number'  => 'INV-' . date('Y') . '-' . str_pad($invoiceNo, 4, '0', STR_PAD_LEFT),
                    'invoice_date'    => now()->subDays(random_int(1, 25)),
                    'due_date'        => now()->addDays(15),
                    'party_id'        => $party->id,
                    'place_of_supply' => $party->state_code ?? $tenant->state_code,
                    'is_interstate'   => $rates['is_interstate'],
                    'status'          => 'draft',
                    'source'          => 'manual',
                    'subtotal'        => $taxable,
                    'taxable_amount'  => $taxable,
                    'cgst_amount'     => $tax['cgst_amount'],
                    'sgst_amount'     => $tax['sgst_amount'],
                    'igst_amount'     => $tax['igst_amount'],
                    'total_tax'       => $tax['total_tax'],
                    'total_amount'    => round($taxable + $tax['total_tax'], 2),
                    'balance_amount'  => round($taxable + $tax['total_tax'], 2),
                    'created_by'      => $user->id,
                ]);

                $invoice->lines()->create([
                    'tenant_id'         => $tenant->id,
                    'line_order'        => 0,
                    'description'       => $item['desc'],
                    'hsn_sac_code'      => $item['hsn'],
                    'quantity'          => $qty,
                    'unit'              => 'nos',
                    'rate'              => $rate,
                    'taxable_amount'    => $taxable,
                    'cgst_rate'         => $rates['cgst_rate'],
                    'cgst_amount'       => $tax['cgst_amount'],
                    'sgst_rate'         => $rates['sgst_rate'],
                    'sgst_amount'       => $tax['sgst_amount'],
                    'igst_rate'         => $rates['igst_rate'],
                    'igst_amount'       => $tax['igst_amount'],
                    'line_total'        => $taxable,
                ]);

                $posting->post($invoice->fresh(), $user->id);
                $invoiceNo++;
            }
        }

        $this->command?->info("Demo tenant '{$tenant->name}' seeded — login demo@lekhya.app / demo12345");
    }
}
