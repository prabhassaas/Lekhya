# Lekhya — AI-Enabled GST Accounting ERP for India

> *Seedha-saadha* (simple, no jargon) accounting for Indian businesses and CAs.
> Part of [Prabhas SaaS](https://prabhassaas.in) — "One login. Every app."

## What is Lekhya?

Lekhya is a **GST-compliant, double-entry accounting ERP** for India, built with PHP/Laravel + MySQL. It connects natively to **Seedha Bill** (GST billing app) and includes an AI layer for invoice extraction, reconciliation, and natural-language queries.

### Editions

| Edition | Who it's for |
|---------|-------------|
| **Lekhya Standard** | Businesses doing their own accounts |
| **Lekhya Pramaan** | Chartered Accountants — adds UDIN, DSC, audit forms, compliance calendar |

## Stack

- **Backend:** PHP 8.4 + Laravel 11
- **Database:** MySQL 8.0 (multi-tenant, scoped by `tenant_id`)
- **Frontend:** Blade templates + Alpine.js + Tailwind CSS
- **Queue:** Redis + Laravel Queue (BullMQ equivalent)
- **Payments:** Razorpay recurring
- **AI:** Local LLM via Ollama (Llama 3.2 by default), or Anthropic API

## Quick Start (Local Dev)

```bash
# Clone
git clone https://github.com/prabhassaas/lekhya
cd lekhya/lekhya-app

# Install PHP dependencies
composer install

# Set up environment
cp .env.example .env
php artisan key:generate

# Edit .env — set DB credentials, keep GST_DRIVER=mock for dev

# Run migrations + seed
php artisan migrate
php artisan db:seed

# Start dev server
php artisan serve          # http://localhost:8000
php artisan queue:work     # (separate terminal — for AI + connector jobs)
```

## Key Modules

| Module | Location | Responsibility |
|--------|----------|----------------|
| Accounting | `app/Services/Accounting/` | CoA, Journal engine, Invoices, Reports, Tally import |
| GST | `app/Services/GST/` | GSTIN validation, Rate engine, IRN, GSTR filing |
| Connector | `app/Services/Connector/` | Seedha Bill adapter, Import pipeline, Token management |
| AI | `app/Http/Controllers/AI/` | OCR extraction, NL queries, Reconciliation suggestions |
| Pramaan | `app/Http/Controllers/Pramaan/` | UDIN, DSC, Audit reports, Compliance calendar |
| Billing | `app/Models/Plan.php`, `Subscription.php` | SaaS plans, Razorpay, Dunning |

## Architecture Principles

1. **Double-entry enforced** — `JournalEngine::post()` validates debit=credit before writing. Posted journals are immutable; corrections = reversing entries.
2. **AI proposes, human approves** — AI never writes to the ledger directly.
3. **GST via gateway only** — All GST API calls go through `GstGateway` interface. Swap `MockGstGateway` → real GSP class in `AppServiceProvider`.
4. **Connector is idempotent** — Same invoice ID from Seedha Bill is deduped. Once posted, the source invoice is locked.
5. **Multi-tenant scoped** — Every Eloquent query scoped to `tenant_id`. `TenantMiddleware` enforces auth.

## Seedha Bill Connector

```
Mode A (same Prabhas account):
  Seedha Bill → shared Supabase table → Lekhya reads via RPC → ImportPipeline → MySQL

Mode B (different accounts):
  Accountant generates token → Freelancer pastes in Seedha Bill
  → REST webhook → ImportPipeline → MySQL
```

See `resources/views/marketing/connector-guide.blade.php` for full technical setup.

## GST API Integration

Lekhya uses a `GstGateway` interface. For production:
1. Register with a GSP (Masters India, ClearTax, IRIS, etc.)
2. Set `GST_DRIVER=masters_india` in `.env`
3. Implement `MastersIndiaGateway implements GstGateway`
4. Register in `AppServiceProvider`

See `/help/gst-api` in the app for the full guide.

## Local LLM (AI Features)

```bash
# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh
ollama pull llama3.2

# Configure Lekhya
AI_DRIVER=ollama
AI_ENDPOINT=http://localhost:11434/api/generate
AI_MODEL=llama3.2
```

See `/help/local-llm` for model recommendations and hardware requirements.

## Tally Migration

Export your Tally data as XML → upload via Accounting → Tally Migration wizard.
Lekhya imports: ledgers, parties, and all vouchers (sales, purchase, receipt, payment, journal, contra).

See `/flows` in the app for the complete migration flow, or `/help/tally-migration`.

## Deploy on Hostinger

Full step-by-step guide at `/help/hostinger-deploy` in the running app, or in:
`resources/views/marketing/help/hostinger-deploy.blade.php`

**Short version:**
```bash
# On Hostinger VPS (Ubuntu 22.04)
apt install -y nginx php8.4-fpm php8.4-mysql redis-server
# clone + composer install + .env + migrate + nginx config
# Use supervisor for queue workers
# certbot for SSL
```

## License

MIT — Free to use, modify, and deploy.

---

*Lekhya means "writing" in Sanskrit — keeping your books.*
