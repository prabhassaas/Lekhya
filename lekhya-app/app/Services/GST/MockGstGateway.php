<?php

namespace App\Services\GST;

use Illuminate\Support\Str;

class MockGstGateway implements GstGateway
{
    public function validateGstin(string $gstin): array
    {
        $gstin = strtoupper(trim($gstin));
        $valid = $this->checkGstinFormat($gstin);

        if (! $valid) {
            return ['valid' => false, 'gstin' => $gstin, 'message' => 'GSTIN format looks invalid', 'provider' => 'mock'];
        }

        return [
            'valid'             => true,
            'gstin'             => $gstin,
            'legal_name'        => 'MOCK TRADER PRIVATE LIMITED',
            'trade_name'        => 'Mock Trader',
            'status'            => 'Active',
            'registration_date' => '2017-07-01',
            'constitution'      => 'Private Limited Company',
            'taxpayer_type'     => 'Regular',
            'address'           => '123 Commercial Street, Bengaluru, Karnataka, 560001',
            'state_code'        => substr($gstin, 0, 2),
            'pan'               => substr($gstin, 2, 10),
            'provider'          => 'mock',
        ];
    }

    public function generateIrn(array $payload): array
    {
        return [
            'irn'        => Str::random(64),
            'ack_no'     => (string) rand(100000000000000, 999999999999999),
            'ack_date'   => now()->format('Y-m-d H:i:s'),
            'signed_qr'  => 'MOCK_QR_' . base64_encode(json_encode($payload)),
            'status'     => '1',
        ];
    }

    public function cancelIrn(string $irn, string $reason): array
    {
        return ['status' => '1', 'irn' => $irn, 'cancelled_at' => now()->toIsoString()];
    }

    public function generateEwayBill(array $payload): array
    {
        return [
            'ewb_no'     => (string) rand(100000000000, 999999999999),
            'ewb_date'   => now()->format('Y-m-d H:i:s'),
            'valid_upto' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'status'     => '1',
        ];
    }

    public function getGstr2b(string $gstin, string $returnPeriod): array
    {
        return [
            'gstin'           => $gstin,
            'return_period'   => $returnPeriod,
            'b2b'             => [],
            'cdnr'            => [],
            'impgsez'         => [],
            'generated_on'    => now()->toIsoString(),
        ];
    }

    public function fileGstr1(string $gstin, string $returnPeriod, array $data): array
    {
        return [
            'status'          => 'P',
            'arn'             => 'AA' . date('m') . date('Y') . rand(1000000, 9999999),
            'return_period'   => $returnPeriod,
            'filed_at'        => now()->toIsoString(),
        ];
    }

    private function checkGstinFormat(string $gstin): bool
    {
        // GSTIN format: 2 state code + 10 PAN + 1 entity + 1 check digit
        if (strlen($gstin) !== 15) return false;
        return (bool) preg_match('/^[0-3][0-9][A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstin);
    }
}
