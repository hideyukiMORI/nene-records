# NeNe Records

[![Backend CI](https://github.com/hideyukiMORI/nene-records/actions/workflows/backend-ci.yml/badge.svg)](https://github.com/hideyukiMORI/nene-records/actions/workflows/backend-ci.yml)
[![Frontend CI](https://github.com/hideyukiMORI/nene-records/actions/workflows/frontend-ci.yml/badge.svg)](https://github.com/hideyukiMORI/nene-records/actions/workflows/frontend-ci.yml)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](./LICENSE)
[![OpenAPI](https://img.shields.io/badge/OpenAPI-3.1-85EA2D?logo=swagger)](./docs/openapi/openapi.yaml)

API-first flexible entity platform built on [NENE2](https://github.com/hideyukiMORI/NENE2).

NeNe Records lets you define entity types and typed fields from the admin frontend, persist them through a thin JSON API, and expose the same operations to humans, SPAs, and AI agents via OpenAPI and MCP.

## Goals

- **Lighter than WordPress**: no monolithic PHP runtime, no untyped post meta chaos
- **Readable**: explicit schema registry, typed value tables, OpenAPI contracts
- **Secure**: auth and validation at the API boundary; MCP tools never touch the database directly
- **Replaceable layers**: swap the API runtime, admin UI, or consumer views independently
- **AI-native**: 130+ MCP tools expose every API operation to AI clients
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

Default credentials (local only):

- Admin: `admin@nene-records.local` / `nene1234`
- Editor: `editor@nene-records.local` / `nene1234`

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
- **MCP**: 130+ tools auto-generated from OpenAPI
- **Multi-tenancy**: organization-scoped data isolation; `OrgResolverMiddleware` + JWT `org_id` claim

## Current Status

Milestones M1 – M15 are complete (admin platform). Active work — single-origin crawlable SSR, WordPress (WXR) migration, and production deployment — is tracked in [`docs/todo/current.md`](./docs/todo/current.md); see [`docs/roadmap.md`](./docs/roadmap.md) for direction.

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
| MCP tools | 130+ tools auto-generated from OpenAPI |
| CI | Backend (940 PHPUnit tests / PHPStan level 8 / CS-Fixer / OpenAPI / MCP) + Frontend (ESLint / TypeScript / Vitest) + Playwright 220 E2E tests |

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
