<?php

namespace Database\Seeders;

use App\Models\InvoiceLine;
use App\Models\Party;
use Illuminate\Database\Seeder;

/**
 * Set a classification (customer / vendor / service_provider) on parties that
 * don't have one yet, inferred from their bills: a party billed mostly under
 * SAC service codes (chapter 99) is a service provider. Idempotent — only
 * touches parties with a null classification, so it's safe to re-run.
 */
class BackfillPartyClassificationSeeder extends Seeder
{
    public function run(): void
    {
        Party::whereNull('classification')->chunkById(200, function ($parties) {
            foreach ($parties as $p) {
                if ($p->type === 'customer') {
                    $p->update(['classification' => 'customer']);
                    continue;
                }

                $codes = InvoiceLine::where('tenant_id', $p->tenant_id)
                    ->whereIn('invoice_id', $p->invoices()->pluck('id'))
                    ->pluck('hsn_sac_code')->filter(fn ($c) => filled($c));

                $sac   = $codes->filter(fn ($c) => str_starts_with((string) $c, '99'))->count();
                $total = $codes->count();

                $p->update([
                    'classification' => ($total > 0 && $sac / $total >= 0.5) ? 'service_provider' : 'vendor',
                ]);
            }
        });
    }
}
