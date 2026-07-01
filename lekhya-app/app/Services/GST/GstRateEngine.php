<?php

namespace App\Services\GST;

use App\Models\HsnSacCode;

class GstRateEngine
{
    /**
     * Returns applicable GST rates for a line item.
     * State code '29' = Karnataka (same state = CGST+SGST; different = IGST).
     */
    public function getRates(string $hsnCode, string $supplierStateCode, string $buyerStateCode): array
    {
        $hsn = HsnSacCode::where('code', $hsnCode)->first();

        if (! $hsn) {
            return $this->defaultRates($supplierStateCode, $buyerStateCode);
        }

        $isInterstate = $supplierStateCode !== $buyerStateCode;

        if ($isInterstate) {
            return [
                'is_interstate' => true,
                'igst_rate'     => $hsn->igst_rate,
                'cgst_rate'     => 0,
                'sgst_rate'     => 0,
                'cess_rate'     => $hsn->cess_rate,
            ];
        }

        return [
            'is_interstate' => false,
            'igst_rate'     => 0,
            'cgst_rate'     => $hsn->cgst_rate,
            'sgst_rate'     => $hsn->sgst_rate,
            'cess_rate'     => $hsn->cess_rate,
        ];
    }

    public function calculateTax(float $taxableAmount, array $rates): array
    {
        $cgst = round($taxableAmount * $rates['cgst_rate'] / 100, 4);
        $sgst = round($taxableAmount * $rates['sgst_rate'] / 100, 4);
        $igst = round($taxableAmount * $rates['igst_rate'] / 100, 4);
        $cess = round($taxableAmount * ($rates['cess_rate'] ?? 0) / 100, 4);

        return [
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => $igst,
            'cess_amount' => $cess,
            'total_tax'   => $cgst + $sgst + $igst + $cess,
        ];
    }

    private function defaultRates(string $supplier, string $buyer): array
    {
        $isInterstate = $supplier !== $buyer;
        return [
            'is_interstate' => $isInterstate,
            'igst_rate'     => $isInterstate ? 18 : 0,
            'cgst_rate'     => $isInterstate ? 0 : 9,
            'sgst_rate'     => $isInterstate ? 0 : 9,
            'cess_rate'     => 0,
        ];
    }
}
