<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\InvoiceController;
use App\Http\Controllers\Accounting\PartyController;
use App\Http\Controllers\Accounting\ProductController;
use App\Http\Controllers\Accounting\PaymentController;
use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Accounting\ReportController;
use App\Http\Controllers\Accounting\TallyImportController;
use App\Http\Controllers\Banking\BankReconciliationController;
use App\Http\Controllers\GST\GstController;
use App\Http\Controllers\GST\GstinLookupController;
use App\Http\Controllers\Connector\ConnectorController;
use App\Http\Controllers\AI\AiAssistantController;
use App\Http\Controllers\Pramaan\UdinController;
use App\Http\Controllers\Pramaan\AuditReportController;
use App\Http\Controllers\Pramaan\ComplianceCalendarController;
use App\Http\Controllers\Pramaan\DscController;
use App\Http\Controllers\Pramaan\WorkingPaperController;
use App\Http\Controllers\Pramaan\NoticeController;
use App\Http\Controllers\Marketing\MarketingController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\SuperAdminController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Settings\UserManagementController;
use App\Http\Controllers\Settings\AiSettingsController;
use Illuminate\Support\Facades\Route;

// ── Marketing / Public pages
Route::get('/', [MarketingController::class, 'home'])->name('marketing.home');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('marketing.pricing');
Route::get('/features', [MarketingController::class, 'features'])->name('marketing.features');
Route::get('/about', [MarketingController::class, 'about'])->name('marketing.about');
Route::get('/contact', [MarketingController::class, 'contact'])->name('marketing.contact');
Route::get('/help', [MarketingController::class, 'help'])->name('marketing.help');
Route::get('/help/{topic}', [MarketingController::class, 'helpTopic'])->name('marketing.help.topic');
Route::get('/flows', [MarketingController::class, 'flows'])->name('marketing.flows');
Route::get('/seedha-bill-connector', [MarketingController::class, 'connectorGuide'])->name('marketing.connector');

// ── Public GSTIN verification (onboarding auto-fill; throttled, read-only)
Route::get('/gstin/verify', [GstinLookupController::class, 'verify'])
    ->middleware('throttle:20,1')->name('gstin.verify');

// ── Auth
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register', [LoginController::class, 'showRegister'])->name('register');
Route::post('/register', [LoginController::class, 'register']);

// ── Prabhas SSO (Brief 1)
Route::get('/auth/sso', [SsoController::class, 'handle'])->name('sso.handle');
Route::get('/auth/sso/logout', [SsoController::class, 'logout'])->name('sso.logout');

// ── Google / Supabase OAuth
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::post('/auth/google/verify', [GoogleAuthController::class, 'verify'])->name('auth.google.verify');

// ── App (authenticated)
Route::middleware(['auth', 'tenant'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Global search
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:60,1')->name('search.suggest');

    // Accounting
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::resource('accounts', AccountController::class);
        Route::get('accounts/{account}/ledger', [AccountController::class, 'ledger'])->name('accounts.ledger');
        Route::resource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/post', [InvoiceController::class, 'post'])->name('invoices.post');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('invoices/{invoice}/reverse', [InvoiceController::class, 'reverse'])->name('invoices.reverse');
        Route::get('invoices/{invoice}/original', [InvoiceController::class, 'original'])->name('invoices.original');

        // Inventory / products (HSN auto-mapped)
        Route::get('hsn-lookup', [ProductController::class, 'hsnLookup'])->name('hsn.lookup');
        Route::resource('products', ProductController::class)->except(['show']);

        // Parties (vendors & customers) — export must precede the {party} route.
        Route::get('parties', [PartyController::class, 'index'])->name('parties.index');
        Route::post('parties/quick', [PartyController::class, 'quickStore'])->name('parties.quick');
        Route::post('parties/{party}/extract', [PartyController::class, 'extractDetails'])->name('parties.extract');
        Route::get('parties/export', [PartyController::class, 'export'])->name('parties.export');
        Route::get('parties/{party}/edit', [PartyController::class, 'edit'])->name('parties.edit');
        Route::get('parties/{party}', [PartyController::class, 'show'])->name('parties.show');
        Route::put('parties/{party}', [PartyController::class, 'update'])->name('parties.update');
        Route::delete('parties/{party}', [PartyController::class, 'destroy'])->name('parties.destroy');

        // Pending payments (payables / receivables) from recorded bills.
        Route::get('payments/pending', [PaymentController::class, 'pending'])->name('payments.pending');
        Route::get('payments/pending/export', [PaymentController::class, 'export'])->name('payments.export');
        // Bank payment-file builder (invoice-wise NEFT/RTGS upload per bank).
        Route::get('payments/bank-file', [PaymentController::class, 'bankFile'])->name('payments.bankfile');
        Route::get('payments/bank-file/{bank}', [PaymentController::class, 'exportBank'])->name('payments.bankfile.download');
        Route::resource('journals', JournalController::class);
        Route::post('journals/{journal}/reverse', [JournalController::class, 'reverse'])->name('journals.reverse');
        Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.pl');
        Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.bs');
        Route::get('reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.tb');
        Route::get('reports/ar-aging', [ReportController::class, 'arAging'])->name('reports.ar');
        Route::get('reports/ap-aging', [ReportController::class, 'apAging'])->name('reports.ap');
        Route::get('reports/{type}/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
        Route::get('tally-import', [TallyImportController::class, 'index'])->name('tally.index');
        Route::post('tally-import/upload', [TallyImportController::class, 'upload'])->name('tally.upload');
        Route::get('tally-import/{import}/preview', [TallyImportController::class, 'preview'])->name('tally.preview');
        Route::post('tally-import/{import}/run', [TallyImportController::class, 'run'])->name('tally.run');
    });

    // Banking / Reconciliation
    Route::prefix('banking')->name('banking.')->group(function () {
        Route::get('/', [BankReconciliationController::class, 'index'])->name('index');
        Route::post('accounts', [BankReconciliationController::class, 'createAccount'])->name('accounts.store');
        Route::post('import-passbook', [BankReconciliationController::class, 'importPassbook'])->name('import');
        Route::get('reconcile/{bankAccount}', [BankReconciliationController::class, 'reconcile'])->name('reconcile');
        Route::post('match', [BankReconciliationController::class, 'match'])->name('match');
        Route::post('complete', [BankReconciliationController::class, 'complete'])->name('complete');
    });

    // GST
    Route::prefix('gst')->name('gst.')->group(function () {
        Route::get('/', [GstController::class, 'dashboard'])->name('dashboard');
        Route::get('validate-gstin', [GstController::class, 'validateGstin'])->name('validate');
        Route::get('gstr1', [GstController::class, 'gstr1'])->name('gstr1');
        Route::post('gstr1/generate', [GstController::class, 'generateGstr1'])->name('gstr1.generate');
        Route::post('gstr1/file', [GstController::class, 'fileGstr1'])->name('gstr1.file');
        Route::get('gstr3b', [GstController::class, 'gstr3b'])->name('gstr3b');
        Route::get('gstr2b', [GstController::class, 'gstr2b'])->name('gstr2b');
        Route::post('gstr2b/import', [GstController::class, 'importGstr2b'])->name('gstr2b.import');
        Route::get('gstr2b/reconcile', [GstController::class, 'reconcile2b'])->name('gstr2b.reconcile');
        Route::get('e-invoice/{invoice}', [GstController::class, 'eInvoice'])->name('einvoice');
        Route::post('e-invoice/{invoice}/generate', [GstController::class, 'generateIrn'])->name('einvoice.generate');
    });

    // Connector
    Route::prefix('connector')->name('connector.')->group(function () {
        Route::get('/', [ConnectorController::class, 'index'])->name('index');
        Route::post('tokens', [ConnectorController::class, 'generateToken'])->name('tokens.generate');
        Route::delete('tokens/{token}', [ConnectorController::class, 'revokeToken'])->name('tokens.revoke');
        Route::get('queue', [ConnectorController::class, 'queue'])->name('queue');
        Route::post('queue/{item}/approve', [ConnectorController::class, 'approveQueued'])->name('queue.approve');
        Route::post('queue/{item}/reject', [ConnectorController::class, 'rejectQueued'])->name('queue.reject');
        Route::post('sync', [ConnectorController::class, 'triggerSync'])->name('sync');
    });

    // AI Assistant
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/', [AiAssistantController::class, 'index'])->name('index');
        Route::get('credits', [AiAssistantController::class, 'credits'])->name('credits');
        Route::post('extract', [AiAssistantController::class, 'extractInvoice'])->name('extract');
        Route::post('query', [AiAssistantController::class, 'naturalLanguageQuery'])->name('query');
        Route::post('suggest-account', [AiAssistantController::class, 'suggestAccount'])->name('suggest-account');
        Route::post('suggestions/{suggestion}/approve', [AiAssistantController::class, 'approve'])->name('approve');
        Route::post('suggestions/{suggestion}/reject', [AiAssistantController::class, 'reject'])->name('reject');
        // Duplicate-vendor resolution (branch vs separate) after approval.
        Route::get('suggestions/{suggestion}/resolve', [AiAssistantController::class, 'resolveDuplicate'])->name('resolve');
        Route::post('suggestions/{suggestion}/resolve', [AiAssistantController::class, 'storeResolve'])->name('resolve.store');
    });

    // Lekhya Pramaan (CA Edition)
    Route::middleware('pramaan')->prefix('pramaan')->name('pramaan.')->group(function () {
        Route::resource('udin', UdinController::class);

        Route::resource('audit-reports', AuditReportController::class);
        Route::post('audit-reports/{audit_report}/transition', [AuditReportController::class, 'transition'])->name('audit-reports.transition');

        Route::get('compliance-calendar', [ComplianceCalendarController::class, 'index'])->name('calendar');
        Route::post('compliance-calendar', [ComplianceCalendarController::class, 'store'])->name('calendar.store');
        Route::patch('compliance-calendar/{item}', [ComplianceCalendarController::class, 'update'])->name('calendar.update');

        Route::get('clients', [ComplianceCalendarController::class, 'clients'])->name('clients');

        Route::get('dsc', [DscController::class, 'index'])->name('dsc.index');
        Route::post('dsc', [DscController::class, 'store'])->name('dsc.store');
        Route::delete('dsc/{dsc}', [DscController::class, 'destroy'])->name('dsc.destroy');

        Route::get('working-papers', [WorkingPaperController::class, 'index'])->name('papers.index');
        Route::post('working-papers', [WorkingPaperController::class, 'store'])->name('papers.store');
        Route::delete('working-papers/{paper}', [WorkingPaperController::class, 'destroy'])->name('papers.destroy');

        Route::get('notices', [NoticeController::class, 'index'])->name('notices.index');
        Route::post('notices', [NoticeController::class, 'store'])->name('notices.store');
        Route::patch('notices/{notice}', [NoticeController::class, 'update'])->name('notices.update');
        Route::delete('notices/{notice}', [NoticeController::class, 'destroy'])->name('notices.destroy');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', fn() => redirect()->route('settings.company'))->name('index');
        Route::get('company', [TenantController::class, 'edit'])->name('company');
        Route::put('company', [TenantController::class, 'update'])->name('company.update');
        Route::get('fiscal-years', [TenantController::class, 'fiscalYears'])->name('fiscal_years');
        Route::post('fiscal-years', [TenantController::class, 'storeFiscalYear'])->name('fiscal_years.store');
        Route::patch('fiscal-years/{fiscalYear}/current', [TenantController::class, 'setCurrentFiscalYear'])->name('fiscal_years.current');
        Route::get('billing', [TenantController::class, 'billing'])->name('billing');
        Route::post('billing/test-invoice', [TenantController::class, 'testInvoice'])->name('billing.test');

        // AI / OCR configuration (per-tenant Groq key, encrypted)
        Route::get('ai', [AiSettingsController::class, 'edit'])->name('ai');
        Route::put('ai', [AiSettingsController::class, 'update'])->name('ai.update');
        Route::post('ai/test', [AiSettingsController::class, 'test'])->name('ai.test');

        // Users & RBAC (Brief 1B)
        Route::get('users', [UserManagementController::class, 'index'])->name('users');
        Route::post('users/invite', [UserManagementController::class, 'invite'])->name('users.invite');
        Route::patch('users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.role');
        Route::patch('users/{user}/permissions', [UserManagementController::class, 'updatePermissions'])->name('users.permissions');
        Route::patch('users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('users.deactivate');
        Route::patch('users/{user}/reactivate', [UserManagementController::class, 'reactivate'])->name('users.reactivate');
        Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });
});

// Connector webhook (unauthenticated)
Route::post('/api/connector/webhook', [ConnectorController::class, 'webhook'])->name('connector.webhook');

// ── Super Admin Panel (Brief 2)
Route::middleware(['auth', \App\Http\Middleware\SuperAdminMiddleware::class])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('tenants', [SuperAdminController::class, 'tenants'])->name('tenants');
        Route::get('tenants/{tenant}', [SuperAdminController::class, 'tenant'])->name('tenants.show');
        Route::post('impersonate/{user}', [SuperAdminController::class, 'impersonate'])->name('impersonate');
        Route::post('stop-impersonating', [SuperAdminController::class, 'stopImpersonating'])->name('stop-impersonating');
        Route::get('feature-flags', [SuperAdminController::class, 'featureFlags'])->name('feature-flags');
        Route::post('feature-flags/toggle', [SuperAdminController::class, 'toggleFeatureFlag'])->name('feature-flags.toggle');
        Route::get('audit-log', [SuperAdminController::class, 'auditLog'])->name('audit-log');
    });
