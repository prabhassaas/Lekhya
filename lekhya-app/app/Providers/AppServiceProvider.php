<?php

namespace App\Providers;

use App\Services\Accounting\ChartOfAccountsSeeder;
use App\Services\Accounting\InvoicePostingService;
use App\Services\Accounting\JournalEngine;
use App\Services\Accounting\TallyMigrationService;
use App\Services\Connector\ImportPipeline;
use App\Services\Connector\InvoiceSourceAdapter;
use App\Services\Connector\SeedhaBillAdapter;
use App\Services\GST\GstGateway;
use App\Services\GST\GstRateEngine;
use App\Services\GST\MockGstGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind GST gateway — swap to real GSP class in production
        $this->app->singleton(GstGateway::class, function () {
            return match (config('services.gst.driver', 'mock')) {
                'mock'  => new MockGstGateway(),
                default => new MockGstGateway(), // replace with real GSP class
            };
        });

        $this->app->singleton(GstRateEngine::class);
        $this->app->singleton(JournalEngine::class);
        $this->app->singleton(ChartOfAccountsSeeder::class);

        $this->app->singleton(InvoicePostingService::class, function ($app) {
            return new InvoicePostingService($app->make(JournalEngine::class));
        });

        $this->app->singleton(TallyMigrationService::class, function ($app) {
            return new TallyMigrationService($app->make(JournalEngine::class));
        });

        $this->app->singleton(InvoiceSourceAdapter::class, function () {
            return new SeedhaBillAdapter(config('services.seedha_bill.mode', 'mock'));
        });

        $this->app->singleton(ImportPipeline::class, function ($app) {
            return new ImportPipeline(
                $app->make(InvoiceSourceAdapter::class),
                $app->make(GstGateway::class),
                $app->make(GstRateEngine::class),
                $app->make(InvoicePostingService::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
