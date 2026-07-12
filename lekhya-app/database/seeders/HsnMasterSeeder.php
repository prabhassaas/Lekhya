<?php

namespace Database\Seeders;

use App\Models\HsnSacCode;
use Illuminate\Database\Seeder;

/**
 * A curated HSN/SAC master with standard GST rates — a practical starting set
 * (textiles-heavy for yarn/apparel businesses, common goods, and common
 * services), not the full CBIC list. Rates are indicative and editable; the
 * per-tenant learning memory refines them from each tenant's real invoices.
 * Idempotent (updateOrCreate by code) — safe to run on every deploy.
 */
class HsnMasterSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->hsn() as [$code, $desc, $rate]) {
            $this->put((string) $code, $desc, (float) $rate, 'hsn');
        }
        foreach ($this->sac() as [$code, $desc, $rate]) {
            $this->put((string) $code, $desc, (float) $rate, 'sac');
        }
    }

    private function put(string $code, string $desc, float $rate, string $type): void
    {
        HsnSacCode::updateOrCreate(
            ['code' => $code],
            [
                'type'        => $type,
                'description' => $desc,
                'cgst_rate'   => round($rate / 2, 2),
                'sgst_rate'   => round($rate / 2, 2),
                'igst_rate'   => $rate,
                'cess_rate'   => 0,
                'is_active'   => true,
            ]
        );
    }

    /** @return array<array{0:string,1:string,2:float}> */
    private function hsn(): array
    {
        return [
            // ── Textiles: yarn (natural 5%, man-made 12%) ──
            ['5004', 'Silk yarn', 5], ['5006', 'Silk yarn, retail', 5],
            ['5106', 'Wool yarn, carded', 5], ['5107', 'Wool yarn, combed', 5],
            ['5205', 'Cotton yarn (>=85% cotton)', 5], ['5206', 'Cotton yarn (<85% cotton)', 5],
            ['5207', 'Cotton yarn, retail', 5],
            ['5401', 'Sewing thread, man-made filament', 12], ['5402', 'Synthetic filament yarn', 12],
            ['5403', 'Artificial filament yarn', 12], ['5509', 'Synthetic staple fibre yarn', 12],
            ['5510', 'Artificial staple fibre yarn', 12],
            // ── Textiles: fabrics (5%) ──
            ['5208', 'Woven cotton fabric (>=85%)', 5], ['5209', 'Woven cotton fabric, heavy', 5],
            ['5210', 'Woven cotton fabric, blended', 5], ['5211', 'Woven cotton fabric, heavy blended', 5],
            ['5407', 'Woven fabric of synthetic filament', 5], ['5512', 'Woven fabric, synthetic staple', 5],
            ['5513', 'Woven fabric, synthetic/cotton', 5], ['5801', 'Woven pile & chenille fabric', 5],
            ['6001', 'Knitted pile fabric', 5], ['6006', 'Other knitted/crocheted fabric', 5],
            // ── Textiles: apparel & made-ups (12%) ──
            ['6101', 'Men’s overcoats, knitted', 12], ['6104', 'Women’s suits, knitted', 12],
            ['6109', 'T-shirts, singlets, knitted', 12], ['6110', 'Jerseys, pullovers, knitted', 12],
            ['6203', 'Men’s suits & trousers, woven', 12], ['6204', 'Women’s suits & dresses, woven', 12],
            ['6205', 'Men’s shirts, woven', 12], ['6302', 'Bed, table & kitchen linen', 12],
            ['6303', 'Curtains & interior blinds', 12], ['6305', 'Sacks & bags for packing', 5],
            ['5607', 'Twine, cordage, rope', 12], ['6307', 'Other made-up textile articles', 12],
            // ── Electronics & IT hardware (18%) ──
            ['8471', 'Computers & laptops', 18], ['8443', 'Printers & copiers', 18],
            ['8517', 'Telephones & smartphones', 18], ['8528', 'Monitors & displays', 18],
            ['8504', 'Adapters, UPS, transformers', 18], ['8523', 'Storage media', 18],
            ['8544', 'Insulated wire & cable', 18],
            // ── Stationery, paper, plastics (mixed) ──
            ['4802', 'Uncoated paper', 12], ['4820', 'Registers, notebooks, binders', 18],
            ['4901', 'Printed books', 0], ['3923', 'Plastic packing articles', 18],
            ['3926', 'Other articles of plastic', 18],
            // ── Furniture, fixtures, appliances ──
            ['9403', 'Other furniture', 18], ['9405', 'Lamps & lighting fittings', 18],
            ['8414', 'Fans, pumps, compressors', 18], ['8415', 'Air conditioners', 28],
            ['8418', 'Refrigerators & freezers', 28],
            // ── FMCG / misc goods ──
            ['3401', 'Soap', 18], ['3304', 'Cosmetics & make-up', 18], ['3305', 'Hair preparations', 18],
            ['0902', 'Tea', 5], ['1701', 'Sugar', 5], ['2106', 'Food preparations n.e.s.', 18],
        ];
    }

    /** @return array<array{0:string,1:string,2:float}> */
    private function sac(): array
    {
        return [
            ['9954', 'Construction services', 18],
            ['9961', 'Wholesale trade services', 18], ['9962', 'Retail trade services', 18],
            ['9971', 'Financial services', 18], ['9972', 'Real estate services', 18],
            ['9973', 'Leasing or rental services', 18],
            ['9982', 'Legal & accounting services', 18],
            ['9983', 'Other professional, technical & business services', 18],
            ['998311', 'Management consulting services', 18], ['998312', 'Business consulting services', 18],
            ['998313', 'Information technology consulting', 18], ['998314', 'IT design & development services', 18],
            ['998315', 'Hosting & IT infrastructure services', 18], ['998316', 'IT infrastructure & network management', 18],
            ['9985', 'Support services', 18], ['9987', 'Maintenance, repair & installation', 18],
            ['9989', 'Other manufacturing & publishing services', 18], ['9997', 'Other services', 18],
        ];
    }
}
