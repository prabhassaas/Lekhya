<?php

namespace App\Services\GST;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cashfree Verification Suite — real GSTIN verification (legal name, trade name,
 * status, registration date, address, taxpayer type).
 *
 * Cashfree is a *verification* provider, not a GSP: e-invoice (IRN), e-way bill
 * and GSTR filing are not part of its API. Those methods therefore delegate to
 * an injected fallback gateway (the mock today, a real GSP when one is wired).
 * Every GST call still goes through the GstGateway interface — hard rule #4.
 *
 * Docs: POST {base}/verification/gstin with x-client-id / x-client-secret.
 */
class CashfreeGstGateway implements GstGateway
{
    public function __construct(
        private GstGateway $gspFallback,
        private string $clientId,
        private string $clientSecret,
        private string $env = 'production',
    ) {}

    public function validateGstin(string $gstin): array
    {
        $gstin = strtoupper(trim($gstin));

        if (! $this->looksLikeGstin($gstin)) {
            return ['valid' => false, 'gstin' => $gstin, 'message' => 'GSTIN format looks invalid', 'provider' => 'cashfree'];
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'x-client-id'     => $this->clientId,
                    'x-client-secret' => $this->clientSecret,
                    'Content-Type'    => 'application/json',
                ])
                ->post($this->baseUrl() . '/verification/gstin', [
                    'GSTIN'         => $gstin,
                    'business_name' => '',
                ]);

            if (! $response->successful()) {
                Log::warning('Cashfree GSTIN verify failed', ['status' => $response->status(), 'body' => $response->body()]);
                return [
                    'valid'    => false,
                    'gstin'    => $gstin,
                    'message'  => $this->errorMessage($response->json(), $response->status()),
                    'provider' => 'cashfree',
                ];
            }

            return $this->normalize((array) $response->json(), $gstin);
        } catch (\Throwable $e) {
            Log::error('Cashfree GSTIN verify error', ['error' => $e->getMessage()]);
            return ['valid' => false, 'gstin' => $gstin, 'message' => 'Verification service unavailable, try again', 'provider' => 'cashfree'];
        }
    }

    // ── GSP-only operations Cashfree does not offer — delegate to the fallback ──
    public function generateIrn(array $eInvoicePayload): array { return $this->gspFallback->generateIrn($eInvoicePayload); }
    public function cancelIrn(string $irn, string $reason): array { return $this->gspFallback->cancelIrn($irn, $reason); }
    public function generateEwayBill(array $payload): array { return $this->gspFallback->generateEwayBill($payload); }
    public function getGstr2b(string $gstin, string $returnPeriod): array { return $this->gspFallback->getGstr2b($gstin, $returnPeriod); }
    public function fileGstr1(string $gstin, string $returnPeriod, array $data): array { return $this->gspFallback->fileGstr1($gstin, $returnPeriod, $data); }

    private function baseUrl(): string
    {
        return $this->env === 'sandbox' ? 'https://sandbox.cashfree.com' : 'https://api.cashfree.com';
    }

    /** Map Cashfree's response (field names vary by version) into our stable shape. */
    private function normalize(array $b, string $gstin): array
    {
        $status = $this->pick($b, ['gstin_status', 'gstinStatus', 'status']);
        $valid  = array_key_exists('valid', $b) ? (bool) $b['valid'] : ($this->pick($b, ['legal_name_of_business', 'legalNameOfBusiness']) !== null);

        return [
            'valid'             => $valid,
            'gstin'             => $this->pick($b, ['GSTIN', 'gstin']) ?: $gstin,
            'legal_name'        => $this->pick($b, ['legal_name_of_business', 'legalNameOfBusiness', 'legal_name']),
            'trade_name'        => $this->pick($b, ['trade_name_of_business', 'tradeNameOfBusiness', 'trade_name']),
            'status'            => $status,
            'registration_date' => $this->pick($b, ['date_of_registration', 'dateOfRegistration']),
            'constitution'      => $this->pick($b, ['constitution_of_business', 'constitutionOfBusiness']),
            'taxpayer_type'     => $this->pick($b, ['taxpayer_type', 'taxpayerType']),
            'address'           => $this->address($b),
            'state_code'        => strlen($gstin) >= 2 ? substr($gstin, 0, 2) : null,
            'pan'               => strlen($gstin) >= 12 ? substr($gstin, 2, 10) : null,
            'provider'          => 'cashfree',
        ];
    }

    private function address(array $b): ?string
    {
        $ppob = $b['principal_place_of_business_fields'] ?? $b['principalPlaceOfBusinessFields'] ?? null;
        $addr = is_array($ppob)
            ? ($ppob['principal_place_of_business_address'] ?? $ppob['principalPlaceOfBusinessAddress'] ?? null)
            : null;
        $addr ??= $b['address'] ?? $b['principal_place_of_business'] ?? null;

        if (is_string($addr)) {
            return trim($addr) ?: null;
        }
        if (is_array($addr)) {
            if (! empty($addr['address']) && is_string($addr['address'])) {
                return $addr['address'];
            }
            $parts = array_filter($addr, fn ($v) => is_string($v) && trim($v) !== '');
            return $parts ? implode(', ', $parts) : null;
        }
        return null;
    }

    private function pick(array $b, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (isset($b[$k]) && is_scalar($b[$k]) && (string) $b[$k] !== '') {
                return (string) $b[$k];
            }
        }
        return null;
    }

    private function errorMessage(mixed $body, int $status): string
    {
        if (is_array($body)) {
            foreach (['message', 'error', 'error_description'] as $k) {
                if (! empty($body[$k]) && is_string($body[$k])) {
                    return $body[$k];
                }
            }
        }
        return $status === 422 ? 'GSTIN not found in the GST registry' : "GSTIN verification failed (HTTP {$status})";
    }

    private function looksLikeGstin(string $g): bool
    {
        return (bool) preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $g);
    }
}
