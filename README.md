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
- **AI-native**: 60+ MCP tools expose every API operation to AI clients

## Quick Start

```bash
git clone https://github.com/hideyukiMORI/nene-records.git
cd nene-records
cp .env.example .env
docker compose up --build -d
# Admin UI → http://localhost:8080
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
- **MCP**: 60+ tools auto-generated from OpenAPI

## Current Status

**M1 — Usable Blog CMS: complete (2026-05-24)**

**M2 — Team-Ready CMS: in progress** — roles done; i18n infrastructure next

| Area | State |
| --- | --- |
| Entity model | entity_types, entities, 6 typed field tables, relations, tags, entity archive |
| Admin UI | entity types, field defs, records, tags, relations, site settings, login |
| Auth | JWT Bearer — admin / editor roles, capability checks on mutating endpoints (#108) |
| Publish workflow | `draft` / `published` / `archived` + `published_at` |
| Public consumer | `/view/{type}/{slug}`, Markdown body, site settings |
| OpenAPI | 3.1 contract, validated on every CI run |
| MCP tools | 60+ tools auto-generated from OpenAPI |
| CI | Backend (PHPUnit 193 tests / PHPStan / CS-Fixer / OpenAPI / MCP) + Frontend (ESLint / TypeScript / Vitest) |

Next up: **M2 — Team-Ready CMS** (admin i18n #109–#110, entity revisions, image upload, per-record SEO, navigation).
See [`docs/roadmap.md`](./docs/roadmap.md) and [`docs/todo/current.md`](./docs/todo/current.md).

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
