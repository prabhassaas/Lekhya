<?php

namespace App\Services\Connector;

/**
 * Source-agnostic adapter interface for pulling invoices.
 * Implement per source (SeedhaBill, CSV, REST, etc.)
 */
interface InvoiceSourceAdapter
{
    /** Pull pending invoices since the last sync timestamp */
    public function fetchPending(string $sourceId, ?\DateTime $since = null): array;

    /** Acknowledge that an invoice was successfully processed */
    public function acknowledge(string $sourceId, string $externalId, string $status): void;

    /** Mark an invoice as Posted+Locked on the source side */
    public function lockAsPosted(string $sourceId, string $externalId): void;
}
