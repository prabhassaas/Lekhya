# Lekhya — GST ERP Project Memory

## Product

Multi-tenant SaaS — AI-enabled, GST-compliant accounting ERP for India.

## Architecture

Modular monolith. Modules: `accounting`, `gst`, `ai`, `connector`, `billing`, `cms`.
Extract to microservices only when explicitly requested.

## Stack

| Layer | Technology |
|-------|-----------|
| Frontend | Next.js 14 + TypeScript + Tailwind CSS |
| Backend | NestJS + TypeScript |
| Database | PostgreSQL with Row-Level Security (RLS) |
| Cache / Queue | Redis + BullMQ |
| Payments | Razorpay |
| Validation | Zod |
| Testing | Vitest |

## Hard Rules

1. **Multi-tenancy**: every table has `tenant_id`; enforce Postgres RLS on all tables — no exceptions.
2. **Double-entry accounting**: all financial mutations are double-entry journal postings. Posted journals are immutable; corrections are made via reversing entries only.
3. **AI propose-only**: AI never writes to the ledger directly — it proposes, rules validate, a human approves.
4. **GSP gateway**: production GST APIs are reached only through a `GstGateway` interface, never directly. The gateway is mockable for tests.
5. **External integrations** (Seedha Bill, GSP, Razorpay) sit behind interfaces and must be idempotent and retry-safe.

## Conventions

- **Structure**: feature-folder layout — `src/modules/<module>/<feature>/`
- **Commits**: conventional commits (`feat:`, `fix:`, `chore:`, etc.)
- **Validation**: Zod schemas at all API boundaries
- **Tests**: Vitest; test core logic only (journal engine, rate engine, reconciliation) — do not gold-plate
- **Comments**: only when the WHY is non-obvious; no block docstrings

## Module Responsibilities

| Module | Responsibility |
|--------|---------------|
| `accounting` | Chart of accounts, journal engine, parties, invoices, reports |
| `gst` | GSTIN validation, HSN/SAC rates, e-invoice (IRN), e-way bill, GSTR-1/3B/2B |
| `ai` | OCR extraction, auto-coding, reconciliation matching, NL queries, anomaly detection |
| `connector` | `InvoiceSourceAdapter` interface, `SeedhaBillAdapter`, import pipeline, dedup |
| `billing` | SaaS subscription plans, Razorpay recurring, entitlements, dunning |
| `cms` | Block registry, page editor, draft/publish versioning, SEO, media library |

## Phase Status

- [ ] Phase 0 — Foundations (monorepo, Docker, auth, RLS skeleton)
- [ ] Phase 1 — Accounting core (CoA, journal engine, invoices, reports)
- [ ] Phase 2 — GST compliance layer
- [ ] Phase 3 — Seedha Bill connector
- [ ] Phase 4 — AI layer
- [ ] Phase 5a — Subscription / billing manager
- [ ] Phase 5b — Marketing site + CMS + page editor
- [ ] Phase 6 — Hardening & launch

## Session Habits

- Plan before building; wait for approval before writing code
- One phase per session; `/clear` between phases, `/compact` mid-phase
- Reference files by path; don't paste code into prompts
- Commit at the end of each phase
- Constrain scope explicitly — say what NOT to build in every prompt
- Always include acceptance criteria so work stops when done

## Stack (Updated — PHP/Laravel)

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11 (PHP 8.4) |
| Frontend | Blade + Alpine.js + Tailwind CSS CDN |
| Database | MySQL 8.0 with Eloquent ORM |
| Cache/Queue | Redis + Laravel Queue |
| Payments | Razorpay |
| PDF Export | DomPDF (barryvdh/laravel-dompdf) |
| Auth / RBAC | Laravel Sanctum + Spatie Permission |
| Testing | PHPUnit + Pest |

## App Location

Main Laravel application: `/lekhya-app/`

## Phase Status (Updated)

- [x] Core migrations (tenants, accounting, GST, connector, billing, Pramaan)
- [x] Multi-tenant middleware + Pramaan gating
- [x] Double-entry JournalEngine (immutable, balanced-enforced)
- [x] InvoicePostingService (auto-posts DR/CR + GST accounts)
- [x] GST Rate Engine (CGST/SGST vs IGST by state)
- [x] GST Gateway interface + MockGstGateway
- [x] Seedha Bill Connector (Mode A: Supabase RPC, Mode B: REST token)
- [x] ImportPipeline (normalize → dedupe → validate → post → lock)
- [x] TallyMigrationService (XML parse + journal import)
- [x] ChartOfAccountsSeeder (standard Indian GST CoA)
- [x] Marketing site (home, pricing, features, flows, connector guide)
- [x] Help docs (local LLM, GST API, Hostinger deploy, Tally migration)
- [x] All module flow diagrams (flows page)
- [ ] Billing/Razorpay subscription flow (Phase 5a)
- [ ] Full Blade UI polish for all accounting pages
- [ ] Real GSP gateway implementation (connect your GSP)
- [ ] Production Supabase ↔ MySQL bridge testing
