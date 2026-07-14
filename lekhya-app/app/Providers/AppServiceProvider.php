<?php

namespace App\Providers;

use App\Services\Accounting\ChartOfAccountsSeeder;
use App\Services\Accounting\InvoicePostingService;
use App\Services\Accounting\JournalEngine;
use App\Services\Accounting\TallyMigrationService;
use App\Services\Connector\ImportPipeline;
use App\Services\Connector\InvoiceSourceAdapter;
use App\Services\Connector\SeedhaBillAdapter;
use App\Services\GST\CashfreeGstGateway;
use App\Services\GST\GstGateway;
use App\Services\GST\GstRateEngine;
use App\Services\GST\MockGstGateway;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind GST gateway. The GSP layer (e-invoice / GSTR filing) is still the
        // mock until a real GSP is wired. GSTIN verification is real via Cashfree
        // once its credentials are set — CashfreeGstGateway decorates the GSP
        // gateway, doing real GSTIN lookups and delegating everything else.
        $this->app->singleton(GstGateway::class, function () {
            $gsp = match (config('services.gst.driver', 'mock')) {
                default => new MockGstGateway(), // replace with real GSP class
            };

            // Use real Cashfree GSTIN verification whenever its credentials are
            // present — no separate flag needed — unless verification is
            // explicitly forced to the offline mock (GST_VERIFY_DRIVER=mock).
            $cf = config('services.gst.cashfree');
            $hasCreds = ! empty($cf['client_id']) && ! empty($cf['client_secret']);
            if ($hasCreds && config('services.gst.verify_driver') !== 'mock') {
                return new CashfreeGstGateway($gsp, (string) $cf['client_id'], (string) $cf['client_secret'], (string) ($cf['env'] ?? 'production'));
            }

            return $gsp;
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
        // Sidebar active-link highlighting: @active('gst.*') → outputs "active"
        // when the current route matches any given pattern (wildcards allowed).
        Blade::directive('active', function ($expression) {
            return "<?php echo request()->routeIs($expression) ? 'active' : ''; ?>";
        });
    }
}
