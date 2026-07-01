<?php

namespace App\Services\Connector;

use App\Models\ConnectorImportQueue;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Party;
use App\Services\Accounting\InvoicePostingService;
use App\Services\GST\GstGateway;
use App\Services\GST\GstRateEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Source-agnostic import pipeline:
 * ingest → normalize → dedupe → validate → quarantine/post → lock source
 *
 * CRITICAL: A posted invoice is LOCKED immediately so it can never be re-submitted.
 */
class ImportPipeline
{
    public function __construct(
        private InvoiceSourceAdapter $adapter,
        private GstGateway $gst,
        private GstRateEngine $rateEngine,
        private InvoicePostingService $posting,
    ) {}

    public function run(int $tenantId, string $source, string $sourceId, int $userId): array
    {
        $fetched = $this->adapter->fetchPending($sourceId);
        $results = ['posted' => 0, 'duplicate' => 0, 'quarantined' => 0, 'errors' => 0];

        foreach ($fetched as $raw) {
            try {
                $result = $this->processOne($tenantId, $source, $sourceId, $raw, $userId);
                $results[$result]++;
            } catch (\Throwable $e) {
                Log::error('Import pipeline error', ['external_id' => $raw['external_id'] ?? null, 'error' => $e->getMessage()]);
                $results['errors']++;
            }
        }

        return $results;
    }

    private function processOne(int $tenantId, string $source, string $sourceId, array $raw, int $userId): string
    {
        $externalId = $raw['external_id'];

        // 1. DEDUPE — same source+external_id already exists?
        $existing = ConnectorImportQueue::where('tenant_id', $tenantId)
            ->where('source', $source)
            ->where('external_id', $externalId)
            ->first();

        if ($existing && $existing->status === 'posted') {
            return 'duplicate';
        }

        // 2. NORMALIZE
        $normalized = $this->normalize($raw, $tenantId);

        // 3. VALIDATE
        $errors = $this->validate($normalized, $tenantId);

        $queue = ConnectorImportQueue::updateOrCreate(
            ['tenant_id' => $tenantId, 'source' => $source, 'external_id' => $externalId],
            [
                'raw_payload'        => $raw,
                'normalized_payload' => $normalized,
                'status'             => empty($errors) ? 'validated' : 'quarantined',
                'validation_errors'  => $errors ?: null,
                'error_details'      => empty($errors) ? null : implode('; ', $errors),
            ]
        );

        if (! empty($errors)) {
            return 'quarantined';
        }

        // 4. POST to invoice + journal
        return DB::transaction(function () use ($queue, $normalized, $tenantId, $userId, $externalId, $source, $sourceId) {
            $invoice = $this->createInvoice($normalized, $tenantId, $source, $externalId, $userId);
            $this->posting->post($invoice, $userId);

            $queue->update(['status' => 'posted', 'invoice_id' => $invoice->id]);

            // 5. LOCK on source — immutability guarantee
            $this->adapter->lockAsPosted($sourceId, $externalId);
            $this->adapter->acknowledge($sourceId, $externalId, 'posted');

            return 'posted';
        });
    }

    private function normalize(array $raw, int $tenantId): array
    {
        // Map from Seedha Bill / CSV shape to our invoice schema
        return [
            'invoice_number' => $raw['invoice_number'],
            'invoice_date'   => $raw['invoice_date'],
            'due_date'       => $raw['due_date'] ?? null,
            'party_gstin'    => $raw['party_gstin'] ?? null,
            'party_name'     => $raw['party_name'],
            'party_state'    => $raw['party_state_code'] ?? null,
            'lines'          => $raw['lines'] ?? [],
            'total_amount'   => $raw['total_amount'],
        ];
    }

    private function validate(array $data, int $tenantId): array
    {
        $errors = [];

        if (empty($data['invoice_number'])) $errors[] = 'Missing invoice number';
        if (empty($data['invoice_date']))   $errors[] = 'Missing invoice date';
        if (empty($data['party_name']))     $errors[] = 'Missing party name';
        if (empty($data['lines']))          $errors[] = 'No line items';

        if (! empty($data['party_gstin'])) {
            try {
                $result = app(GstGateway::class)->validateGstin($data['party_gstin']);
                if (! $result['valid']) $errors[] = "Invalid GSTIN: {$data['party_gstin']}";
            } catch (\Throwable) {
                // gateway unavailable — skip GSTIN check, note in errors
                $errors[] = 'GSTIN validation skipped (gateway unavailable)';
            }
        }

        return $errors;
    }

    private function createInvoice(array $data, int $tenantId, string $source, string $externalId, int $userId): Invoice
    {
        $tenant = \App\Models\Tenant::findOrFail($tenantId);
        $fiscalYear = \App\Models\FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->firstOrFail();

        // Resolve or create party
        $party = Party::firstOrCreate(
            ['tenant_id' => $tenantId, 'gstin' => $data['party_gstin'] ?: null],
            [
                'name'       => $data['party_name'],
                'type'       => 'customer',
                'state_code' => $data['party_state'] ?? null,
            ]
        );

        $invoice = Invoice::create([
            'tenant_id'      => $tenantId,
            'fiscal_year_id' => $fiscalYear->id,
            'type'           => 'sales',
            'invoice_number' => $data['invoice_number'],
            'invoice_date'   => $data['invoice_date'],
            'due_date'       => $data['due_date'],
            'party_id'       => $party->id,
            'status'         => 'draft',
            'source'         => $source,
            'source_invoice_id' => $externalId,
            'total_amount'   => $data['total_amount'],
            'balance_amount' => $data['total_amount'],
            'created_by'     => $userId,
        ]);

        foreach ($data['lines'] as $i => $line) {
            InvoiceLine::create([
                'tenant_id'      => $tenantId,
                'invoice_id'     => $invoice->id,
                'line_order'     => $i,
                'description'    => $line['description'],
                'hsn_sac_code'   => $line['hsn_sac'] ?? null,
                'quantity'       => $line['quantity'] ?? 1,
                'rate'           => $line['rate'] ?? 0,
                'taxable_amount' => $line['taxable'] ?? 0,
                'cgst_rate'      => $line['cgst_rate'] ?? 0,
                'cgst_amount'    => $line['cgst_amount'] ?? 0,
                'sgst_rate'      => $line['sgst_rate'] ?? 0,
                'sgst_amount'    => $line['sgst_amount'] ?? 0,
                'igst_rate'      => $line['igst_rate'] ?? 0,
                'igst_amount'    => $line['igst_amount'] ?? 0,
                'line_total'     => ($line['taxable'] ?? 0) + ($line['cgst_amount'] ?? 0) + ($line['sgst_amount'] ?? 0) + ($line['igst_amount'] ?? 0),
            ]);
        }

        return $invoice;
    }
}
