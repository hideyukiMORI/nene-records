# NeNe Records

[![Backend CI](https://github.com/hideyukiMORI/nene-records/actions/workflows/backend-ci.yml/badge.svg)](https://github.com/hideyukiMORI/nene-records/actions/workflows/backend-ci.yml)
[![Frontend CI](https://github.com/hideyukiMORI/nene-records/actions/workflows/frontend-ci.yml/badge.svg)](https://github.com/hideyukiMORI/nene-records/actions/workflows/frontend-ci.yml)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](./LICENSE)
[![OpenAPI](https://img.shields.io/badge/OpenAPI-3.1-85EA2D?logo=swagger)](./docs/openapi/openapi.yaml)

API-first flexible entity platform built on [NENE2](https://github.com/hideyukiMORI/NENE2).

> **Separate product** — NeNe Records is a **product that consumes [NENE2](https://github.com/hideyukiMORI/NENE2)**, not a framework: a multi-tenant entity/CMS platform (define typed entity types from the admin UI, persist via a thin JSON API, expose the same ops to humans/SPAs/AI over OpenAPI + MCP). Own repo, own database; siblings integrate over HTTP only. Boundaries are binding:
>
> | Sibling | Boundary |
> | --- | --- |
> | NeNe Corpus | Corpus calls Records' read APIs as an optional CMS upstream — `Corpus → Records API` only, no shared DB, no chat/ingestion in Records ([Corpus ADR 0002](https://github.com/hideyukiMORI/nene-corpus/blob/main/docs/adr/0002-separate-from-nene-records.md)) |
> | NeNe Concierge | Concierge reads Records' API as an optional upstream — one-way, no shared code/DB ([Concierge ADR 0002](https://github.com/hideyukiMORI/nene-concierge/blob/main/docs/adr/0002-separation-from-nene-records-and-corpus.md)) |
> | NeNe Invoice / Deal | Optional HTTP link only — Records is an optional CMS/catalog upstream, separate database, no embedded billing or pipeline ([Invoice ADR 0002](https://github.com/hideyukiMORI/nene-invoice/blob/main/docs/adr/0002-separate-from-sibling-products.md)) |

## Goals

- **Lighter than WordPress**: no monolithic PHP runtime, no untyped post meta chaos
- **Readable**: explicit schema registry, typed value tables, OpenAPI contracts
- **Secure**: auth and validation at the API boundary; MCP tools never touch the database directly
- **Replaceable layers**: swap the API runtime, admin UI, or consumer views independently
- **AI-native**: MCP tools, auto-generated 1:1 from the OpenAPI contract, expose every API operation to AI clients
- **Multi-tenant**: organization-scoped data with superadmin / admin / editor roles and JWT org_id enforcement

## Quick Start

```bash
# NeNe Records uses the NENE2 framework as a sibling checkout (../NENE2), wired in
# via Composer's path repository — clone both side by side:
git clone https://github.com/hideyukiMORI/NENE2.git
git clone https://github.com/hideyukiMORI/nene-records.git
cd nene-records
cp .env.example .env
docker compose up --build -d
# API + health → http://localhost:18082/health
# Admin UI (dev): in another shell run `npm run dev --prefix frontend` → http://localhost:18084
```

Create the first admin via the installer (`composer app:install`) — there are no
default credentials. (A previous default-credential seed was removed for security;
see migration `20260711000000_delete_default_seed_users`.)

> See [`docs/development/docker.md`](./docs/development/docker.md) for full setup details.

## Architecture

```
Admin frontend (React)  ──┐
Consumer views          ──┼──→  NeNe Records API (NENE2)  ──→  MySQL / SQLite
AI clients (MCP)        ──┘
```

- **Backend**: PHP 8.4, NENE2 framework, Clean Architecture (Use Cases / Repositories)
- **Frontend**: React 19, TypeScript, Vite, TailwindCSS v4, React Query
- **API contract**: OpenAPI 3.1 ([`docs/openapi/openapi.yaml`](./docs/openapi/openapi.yaml))
- **MCP**: tools auto-generated 1:1 from the OpenAPI contract
- **Multi-tenancy**: organization-scoped data isolation; `OrgResolverMiddleware` + JWT `org_id` claim

## Current Status

| Phase | Scope | Status |
| --- | --- | --- |
| M1–M16 | Entity model, typed fields, admin UI, auth/multi-tenancy, publish workflow, media, comments | ✅ |
| Post-M16 frontier | Single-origin crawlable SSR, WordPress (WXR) import, public i18n, production deployment | ✅ |
| #741 (org transport, Phase 1+2) | SaaS → Tier A: conflict-merge import, transactional import, column parity, CLI import, menus/widgets/themes/relations/redirects + ID remap | ✅ merged (PR #793, #794) |
| #741 (remaining scope) | Transport coverage for comments / webhooks / notification_channels / user_profiles; media file bundling | 🔄 open (#796, #798) |
| Public-site hardening | Superadmin-only console, CSP trusted-embed allowlist (Phase 1), org-level maintenance mode | ✅ merged (#797, #802 Phase 1, #813) |
| Public-site hardening (remaining) | Trusted-embed Phase 2 (widget primitive) / Phase 3 (media); maintenance-mode admin UI toggle | 🔄 open (#802, #814) |

NeNe Records runs in production as a subdomain multi-tenant SaaS and ships as a self-contained **Tier A zip installer** for shared hosting (current release **v0.5.2**, deployed to production 2026-07-13). Open follow-up work is tracked in [`docs/todo/current.md`](./docs/todo/current.md); see [`docs/roadmap.md`](./docs/roadmap.md) for direction.

| Area | State |
| --- | --- |
| Entity model | entity_types, entities, 6 typed field tables, relations, tags, revisions, archive |
| Admin UI | entity types, field defs, records, tags, SEO, navigation, media library, comments, users |
| Auth | JWT Bearer — superadmin / admin / editor roles, capability checks, org-scoped JWT |
| Multi-tenancy | organizations CRUD, org resolver middleware, per-table org isolation, superadmin UI |
| User management | invite flow, password reset, role management, org membership (1 user = 1 org) |
| Publish workflow | `draft` / `published` / `archived` / `scheduled` + `published_at` |
| Media | upload, library list/delete, image + file field types |
| Comments | submission, moderation API, public comment UI, admin comment management |
| Public consumer | crawlable single-origin SSR at real permalinks (`/posts/{id}`, `{slug}`), OGP/JSON-LD, 301 redirect map, WordPress (WXR) import |
| OpenAPI | 3.1 contract, validated on every CI run |
| MCP tools | auto-generated 1:1 from the OpenAPI contract, covering every endpoint |
| CI | Backend (PHPUnit / PHPStan level 8 / CS-Fixer / OpenAPI / MCP contract tests) + Frontend (ESLint / TypeScript / Vitest) + Playwright E2E |

## Contributing

NeNe Records inherits NENE2 engineering discipline:

| Topic | Document |
| --- | --- |
| **Product vision** | [`docs/explanation/product-vision.md`](./docs/explanation/product-vision.md) |
| **Start here (agents)** | [`AGENTS.md`](./AGENTS.md) |
| NENE2 inheritance map | [`docs/inheritance-from-nene2.md`](./docs/inheritance-from-nene2.md) |
| Workflow | [`docs/workflow.md`](./docs/workflow.md) |
| Commit conventions | [`docs/development/commit-conventions.md`](./docs/development/commit-conventions.md) |
| Coding standards | [`docs/development/coding-standards.md`](./docs/development/coding-standards.md) |
| Docker development | [`docs/development/docker.md`](./docs/development/docker.md) |
| Full contributing guide | [`docs/CONTRIBUTING.md`](./docs/CONTRIBUTING.md) |

**Rules in brief:** GitHub Issue-driven work, `type/issue-number-summary` branches, Conventional Commits (Japanese description, English type), PR with verification and self-review checklist, no direct commits to `main`.

## Related

- [NENE2](https://github.com/hideyukiMORI/NENE2) — PHP micro-framework (HTTP runtime, OpenAPI, MCP)
- [NENE2-FT](https://github.com/hideyukiMORI/NENE2-FT) — field trials and reference implementations

## License

MIT
