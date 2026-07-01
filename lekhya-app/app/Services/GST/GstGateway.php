<?php

namespace App\Services\GST;

/**
 * All GST API calls go through this interface — never directly to the GSP.
 * Swap the implementation in AppServiceProvider to use the real GSP in production.
 */
interface GstGateway
{
    public function validateGstin(string $gstin): array;
    public function generateIrn(array $eInvoicePayload): array;
    public function cancelIrn(string $irn, string $reason): array;
    public function generateEwayBill(array $payload): array;
    public function getGstr2b(string $gstin, string $returnPeriod): array;
    public function fileGstr1(string $gstin, string $returnPeriod, array $data): array;
}
