<?php

namespace App\Console\Commands;

use App\Models\ConnectorConnection;
use App\Services\Connector\ImportPipeline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncConnectorInvoices extends Command
{
    protected $signature = 'connector:sync
                            {--tenant= : Only sync connections for this tenant ID}
                            {--user=1  : User ID to attribute journal postings to}';

    protected $description = 'Pull pending invoices from all active connector connections and post them to Lekhya';

    public function handle(ImportPipeline $pipeline): int
    {
        $tenantFilter = $this->option('tenant');
        $userId       = (int) $this->option('user');

        $query = ConnectorConnection::where('status', 'active');

        if ($tenantFilter) {
            $query->where('tenant_id', (int) $tenantFilter);
        }

        $connections = $query->get();

        if ($connections->isEmpty()) {
            $this->info('No active connector connections found.');
            return self::SUCCESS;
        }

        $this->info("Syncing {$connections->count()} active connection(s)...");

        $totals = ['posted' => 0, 'duplicate' => 0, 'quarantined' => 0, 'errors' => 0];

        foreach ($connections as $connection) {
            $this->line(sprintf(
                '  → tenant=%d  source=%s  external_id=%s',
                $connection->tenant_id,
                $connection->source_label,
                $connection->source_external_id,
            ));

            try {
                $result = $pipeline->run(
                    $connection->tenant_id,
                    $connection->source_label,
                    $connection->source_external_id,
                    $userId,
                );

                foreach (array_keys($totals) as $key) {
                    $totals[$key] += $result[$key] ?? 0;
                }

                $connection->update([
                    'last_sync_at'     => now(),
                    'last_sync_status' => 'ok',
                    'invoices_synced'  => ($connection->invoices_synced ?? 0) + ($result['posted'] ?? 0),
                    'error_message'    => null,
                ]);

                $this->line(sprintf(
                    '    posted=%d  dup=%d  quarantined=%d  errors=%d',
                    $result['posted'],
                    $result['duplicate'],
                    $result['quarantined'],
                    $result['errors'],
                ));
            } catch (\Throwable $e) {
                $this->error("    FAILED: {$e->getMessage()}");

                Log::error('connector:sync pipeline error', [
                    'connection_id' => $connection->id,
                    'tenant_id'     => $connection->tenant_id,
                    'error'         => $e->getMessage(),
                ]);

                $connection->update([
                    'last_sync_at'     => now(),
                    'last_sync_status' => 'error',
                    'error_message'    => $e->getMessage(),
                ]);

                $totals['errors']++;
            }
        }

        $this->info(sprintf(
            'Done. posted=%d  dup=%d  quarantined=%d  errors=%d',
            $totals['posted'],
            $totals['duplicate'],
            $totals['quarantined'],
            $totals['errors'],
        ));

        return self::SUCCESS;
    }
}
