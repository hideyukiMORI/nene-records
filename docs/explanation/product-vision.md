# Product Vision

NeNe Records is a flexible entity platform built on [NENE2](https://github.com/hideyukiMORI/NENE2). This document records why the product exists, what it optimizes for, and how it relates to WordPress and the NENE2 ecosystem.

## Origin

The idea started from a simple question: **can we build something as useful as a CMS, but lighter, more readable, and more secure than WordPress?**

WordPress proved that a generic `posts` table plus extensible metadata can power blogs, shops, and arbitrary applications. It also showed the cost: untyped `postmeta`, hook-driven control flow, theme/plugin coupling, and security surface that grows with every extension.

NeNe Records keeps the **flexibility** while rejecting the **monolith**. The API layer (NENE2) owns persistence, auth, validation, and contracts. The admin frontend owns schema UX and most configuration workflows. Consumer views own public presentation. AI clients (MCP) use the same HTTP boundary as every other client.

## North Star

Developers and AI agents can:

- define entity types and typed fields from an admin frontend
- persist and query records through a documented JSON API
- operate records in natural language via MCP tools
- replace the admin UI, consumer views, or API host without rewriting core data rules

NeNe Records is **not** a PHP framework. It is a **product** that consumes NENE2.

## Philosophy

### 1. API first, always

JSON APIs and OpenAPI are the primary contract. HTML admin and consumer views are clients, not the source of truth.

Inherited from NENE2: `README.md`, `docs/integrations/openapi.md`, `docs/explanation/why-mcp.md`.

### 2. Typed data, not meta chaos

Flexible storage without WordPress-style string-only meta blobs.

- **Entity types** define what can exist (article, product, event, …).
- **Field definitions** register keys, types, and validation rules in a schema registry persisted at the API layer.
- **Typed value storage** uses type-specific tables or documented adapters (text, int, enum, image, file, …).
- **Soft delete** is consistent (`is_deleted`, `deleted_at`) unless an ADR says otherwise.

The frontend may edit schema UX, but **writes must be validated against API-side schema** — otherwise the WordPress plugin/meta problem returns.

### 3. Extreme responsibility separation

| Layer | Responsibility |
| --- | --- |
| **NENE2 runtime** | HTTP, DI, middleware, Problem Details, OpenAPI/MCP plumbing |
| **NeNe Records API** | Entity CRUD, schema registry, auth enforcement, file adapters, analytics endpoints |
| **Admin frontend** | Schema editing UI, management workflows, most configuration UX |
| **Consumer views** | Public list/detail/card patterns over the same API |
| **MCP tools** | AI-facing operations mapped to OpenAPI — never direct database access |

Each layer should be swappable: API host, admin SPA, consumer theme, or MCP catalog can change independently when contracts stay stable.

### 4. AI-native by contract, not by bolt-on

MCP tools expose the same endpoints humans use. Examples the product should eventually support:

- “Show access stats for 05/24”
- “Change the title of that article”
- “List articles tagged `php`”

This works when OpenAPI is complete and MCP tools have clear descriptions — not when agents query SQL directly.

### 5. Readable to humans and agents

Explicit directories, small use cases, typed DTOs, documented ADRs, Issue-driven workflow. Inherited from NENE2 governance (ADR 0001).

## WordPress comparison

| Aspect | WordPress | NeNe Records |
| --- | --- | --- |
| Runtime | Monolithic PHP core + hooks | Thin NENE2 JSON API |
| Metadata | Untyped `postmeta` strings | Typed fields + schema registry |
| Business logic | PHP hooks/filters everywhere | Admin frontend + API validation |
| Public site | Theme-coupled PHP templates | Replaceable consumer views |
| AI access | Ad hoc, plugin-dependent | OpenAPI + MCP catalog |
| Extension model | Plugins touch everything | Contract-bound API clients |

NeNe Records is **not** WordPress-compatible and should not try to be.

## Scope beyond CMS

With schema registry, relations, tags, and query APIs, the same platform can support:

- **Headless CMS** (articles, pages, media)
- **Lightweight app backend** (forms, inventories, simple CRM)
- **AI-operated admin** (natural language CRUD and reporting)

It becomes an app framework only when Phase 3+ features exist (relations, workflow, webhooks, computed fields). Until then, treat it as a **typed entity platform**.

## Performance expectations

Architecture alone does not guarantee speed.

**Likely faster than WordPress when:**

- comparing lean JSON API vs heavy plugin stacks
- querying indexed typed columns instead of generic meta joins
- avoiding hook/bootstrap overhead per request

**Can be slower when:**

- naive EAV joins across many typed tables
- N+1 field loads on list endpoints
- uncached SPA public pages vs fully cached WordPress HTML

Design rules from day one:

- repository queries should eager-load fields in 1–2 SQL round-trips
- consider denormalized read models for hot list endpoints
- cache schema registry and popular entities when needed

## Relationship to NENE2

```
NENE2          → framework (Packagist: hideyukimori/nene2)
NeNe Records   → product (this repository)
NENE2-FT       → reference trials and pattern catalog
```

- Framework changes belong in NENE2.
- Entity model, admin UX, and product MCP tools belong here.
- See `docs/inheritance-from-nene2.md` for governance and doc boundaries.

## Non-goals

- Rebuilding Laravel/Symfony in this repository
- WordPress plugin, theme, or database compatibility
- MCP or admin clients bypassing the HTTP API
- Putting all business logic in PHP when the admin frontend is the intended owner

## Naming note

The working name **NeNe Records** avoids WordPress `posts` baggage. Prefer `entities`, `entity_types`, and `field_defs` in schema design over `posts` / `postmeta`.
