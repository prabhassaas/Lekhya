<?php

namespace App\Services\Connector;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Seedha Bill adapter.
 * Mode A: reads from shared Supabase tables via RPC (same Prabhas account).
 * Mode B: polls the Seedha Bill REST API using a connection token.
 *
 * Replace MOCK responses with real API calls once Seedha Bill API docs are available.
 */
class SeedhaBillAdapter implements InvoiceSourceAdapter
{
    private string $mode;
    private ?string $apiToken;
    private ?string $supabaseUrl;
    private ?string $supabaseKey;

    public function __construct(string $mode = 'mock', ?string $apiToken = null)
    {
        $this->mode         = $mode;
        $this->apiToken     = $apiToken;
        $this->supabaseUrl  = config('services.supabase.url');
        $this->supabaseKey  = config('services.supabase.service_key');
    }

    public function fetchPending(string $sourceId, ?\DateTime $since = null): array
    {
        if ($this->mode === 'mock') {
            return $this->mockInvoices();
        }

        if ($this->mode === 'mode_a') {
            return $this->fetchFromSupabase($sourceId, $since);
        }

        return $this->fetchFromRestApi($sourceId, $since);
    }

    public function acknowledge(string $sourceId, string $externalId, string $status): void
    {
        Log::info("Connector ACK: source={$sourceId} id={$externalId} status={$status}");
        // Real API call would go here
    }

    public function lockAsPosted(string $sourceId, string $externalId): void
    {
        if ($this->mode === 'mode_a') {
            // Call Supabase RPC: lock_invoice_for_lekhya(invoice_id)
            $this->callSupabaseRpc('lock_invoice_for_lekhya', [
                'p_invoice_id' => $externalId,
            ]);
            return;
        }

        // REST API call to Seedha Bill
        if ($this->apiToken && $this->mode === 'mode_b') {
            Http::withToken($this->apiToken)
                ->post(config('services.seedha_bill.base_url') . "/invoices/{$externalId}/lock", [
                    'locked_by' => 'lekhya',
                    'reason'    => 'Posted to Lekhya ERP',
                ]);
        }
    }

    // ── Mode A: read from shared Supabase tables ───────────────────────────
    private function fetchFromSupabase(string $sourceTenantId, ?\DateTime $since): array
    {
        $response = Http::withHeaders([
            'apikey'        => $this->supabaseKey,
            'Authorization' => 'Bearer ' . $this->supabaseKey,
            'Content-Type'  => 'application/json',
        ])->post($this->supabaseUrl . '/rest/v1/rpc/get_lekhya_pending_invoices', [
            'p_tenant_id' => $sourceTenantId,
            'p_since'     => $since?->format('c'),
        ]);

        if ($response->failed()) {
            Log::error('Supabase fetch failed', ['status' => $response->status(), 'body' => $response->body()]);
            return [];
        }

        return collect($response->json())->map(fn($row) => $this->normalizeSupabaseRow($row))->all();
    }

    // ── Mode B: pull from Seedha Bill REST API ─────────────────────────────
    private function fetchFromRestApi(string $sourceId, ?\DateTime $since): array
    {
        $response = Http::withToken($this->apiToken)
            ->get(config('services.seedha_bill.base_url') . '/invoices', [
                'source_id' => $sourceId,
                'since'     => $since?->format('c'),
                'status'    => 'pending_sync',
                'limit'     => 100,
            ]);

        if ($response->failed()) {
            Log::error('Seedha Bill REST fetch failed', ['status' => $response->status()]);
            return [];
        }

        return collect($response->json('data', []))
            ->map(fn($row) => $this->normalizeRestRow($row))
            ->all();
    }

    private function callSupabaseRpc(string $fn, array $params): mixed
    {
        return Http::withHeaders([
            'apikey'        => $this->supabaseKey,
            'Authorization' => 'Bearer ' . $this->supabaseKey,
        ])->post($this->supabaseUrl . "/rest/v1/rpc/{$fn}", $params)->json();
    }

    // ── Normalise rows to a standard invoice shape ─────────────────────────
    private function normalizeSupabaseRow(array $row): array
    {
        return [
            'external_id'      => $row['id'],
            'invoice_number'   => $row['invoice_number'],
            'invoice_date'     => $row['invoice_date'],
            'due_date'         => $row['due_date'] ?? null,
            'party_gstin'      => $row['customer_gstin'] ?? null,
            'party_name'       => $row['customer_name'],
            'party_state_code' => $row['customer_state_code'] ?? null,
            'lines'            => $row['items'] ?? [],
            'total_amount'     => $row['total_amount'],
            'source'           => 'seedha_bill',
        ];
    }

    private function normalizeRestRow(array $row): array
    {
        return $this->normalizeSupabaseRow($row); // same shape in REST API
    }

    private function mockInvoices(): array
    {
        return [
            [
                'external_id'    => 'SB-001',
                'invoice_number' => 'INV-2024-001',
                'invoice_date'   => '2024-04-15',
                'party_gstin'    => '29ABCDE1234F1Z5',
                'party_name'     => 'Mock Customer Pvt Ltd',
                'party_state_code' => '29',
                'lines' => [
                    [
                        'description' => 'Software Services',
                        'hsn_sac'     => '998314',
                        'quantity'    => 1,
                        'rate'        => 10000,
                        'taxable'     => 10000,
                        'cgst_rate'   => 9,
                        'sgst_rate'   => 9,
                        'igst_rate'   => 0,
                    ],
                ],
                'total_amount' => 11800,
                'source'       => 'seedha_bill',
            ],
        ];
    }
}
