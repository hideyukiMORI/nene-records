# Roadmap

NeNe Records is an API-first flexible entity platform on NENE2 — lighter, typed, and MCP-ready compared to WordPress-style CMS monoliths.

## North Star

Developers and AI agents can:

- define entity types and typed fields through an admin frontend
- persist and query records through a documented JSON API
- operate the same data through MCP tools in natural language
- swap admin UI, consumer views, or API hosting independently

## Phase 0: Governance and Foundation

Goal: make contribution, AI operation, and NENE2 inheritance unambiguous.

- README and contribution guide
- Issue-driven workflow and commit conventions
- inheritance map from NENE2
- ADR 0001 (governance inheritance)
- Cursor rules for agents
- self-review checklist policy

Tracked by `docs/milestones/2026-05-governance-and-foundation.md`.

## Phase 1: MVP Entity CRUD

Goal: smallest vertical slice proving the entity model.

Policy (now): `docs/development/backend-standards.md` — NENE2 consumer, domain-grouped modules, zero-tolerance placement.

- `entity_types`, `entities`, `text_fields` tables and migrations
- CRUD API + OpenAPI
- PHPUnit + SQLite tests
- NENE2 consumer project scaffold (`composer.json`, check scripts)

Issue: `#1`

## Phase 2: Additional Field Types

Goal: expand typed storage beyond text.

- int, enum, bool, datetime field tables or unified adapter
- schema registry API (`field_defs`)
- validation against registered schema on write

## Phase 3: Relations, Tags, and Query

Goal: support real CMS/app patterns.

- tag or relation model
- filtered list/search API
- pagination helpers (NENE2 patterns)

## Phase 4: Admin Frontend

Goal: schema and record management UI.

Policy (now): `docs/development/frontend-standards.md` — React + TypeScript strict, layered architecture, MSW tests.

- React + TypeScript admin starter
- entity type editor
- record CRUD screens

## Phase 5: MCP and Analytics

Goal: AI-native operations and basic reporting.

- MCP tool catalog for entity CRUD and queries
- access log + date-based aggregation API
- semantic MCP tools (`listEntitiesByTag`, `getAccessStatsByDate`, etc.)

## Phase 6: Consumer Views

Goal: replaceable public presentation patterns.

- list/detail view templates or SPA shells
- API-only consumer examples

Tracked: Issues #38, #63, #72, #75 — public bootstrap, `/view`, site settings (#80).

## Phase 7: CMS Hardening (Mid-Term)

Goal: operate as a small headless CMS — auth, publish workflow, media, SEO, revisions.

Policy: same API-first boundary; no WordPress compatibility.

- **M1 Usable Blog CMS:** Markdown consumer (#78), publish status, users/auth, public slugs
- **M2 Team-Ready:** roles, entity revisions, image/file upload, navigation, per-record SEO
- **M3 Platform:** full-text search, scheduled publish, webhooks, import/export

Tracked by `docs/milestones/2026-06-cms-mid-term.md` (Issue #82).

## Non-Goals

- Rebuilding Laravel/Symfony-style full-stack framework features in this repo
- WordPress plugin/theme compatibility
- Direct database access from MCP or admin frontend
