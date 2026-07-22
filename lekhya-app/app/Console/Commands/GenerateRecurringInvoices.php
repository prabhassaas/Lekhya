<?php

namespace App\Console\Commands;

use App\Services\Accounting\RecurringInvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring
                            {--tenant= : Only run schedules for this tenant ID}';

    protected $description = 'Raise draft invoices for every recurring schedule that is due today';

    public function handle(RecurringInvoiceService $service): int
    {
        $tenant = $this->option('tenant') ? (int) $this->option('tenant') : null;

        try {
            $raised = $service->runDue($tenant);
        } catch (\Throwable $e) {
            $this->error("Recurring generation failed: {$e->getMessage()}");
            Log::error('invoices:generate-recurring failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $this->info($raised > 0
            ? "Raised {$raised} draft invoice(s) from recurring schedules."
            : 'No recurring schedules were due.');

        return self::SUCCESS;
    }
}
