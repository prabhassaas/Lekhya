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
