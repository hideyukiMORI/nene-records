# Inheritance from NENE2

NeNe Records inherits engineering governance from [NENE2](https://github.com/hideyukiMORI/NENE2). This document is the source of truth for what is inherited, what is adapted, and what is NeNe Records–specific.

## Relationship

| Layer | Repository | Role |
| --- | --- | --- |
| Framework runtime | [NENE2](https://github.com/hideyukiMORI/NENE2) | HTTP runtime, DI, middleware, Problem Details, OpenAPI/MCP patterns |
| Application platform | **NeNe Records** (this repo) | Flexible entity schema, CRUD API, admin/consumer layers |
| Reference trials | [NENE2-FT](https://github.com/hideyukiMORI/NENE2-FT) | Patterns and friction notes from field trials |

NeNe Records is a **consumer project**, not a fork of NENE2. Framework code stays in NENE2; product code stays here.

## Inherited by policy (same rules)

These policies are adopted with the same intent as NENE2. Local copies live in this repository so agents and contributors do not need to guess.

| Topic | Local document |
| --- | --- |
| Issue-driven workflow | `docs/workflow.md` |
| Conventional Commits | `docs/development/commit-conventions.md` |
| Self-review before PR | `docs/development/self-review.md` |
| ADR operation | `docs/development/adr.md` |
| AI agent workflow | `docs/integrations/ai-tools.md`, `AGENTS.md` |
| Cursor summaries | `.cursor/rules/` |

## Inherited by reference (framework behavior)

When implementing HTTP, middleware, validation, or error responses, follow NENE2 upstream docs unless NeNe Records records an explicit deviation in an ADR.

| Topic | NENE2 upstream |
| --- | --- |
| HTTP runtime (PSR-7/15/17) | `docs/development/http-runtime.md` |
| Middleware order and security | `docs/development/middleware-security.md` |
| Request validation layers | `docs/development/request-validation.md` |
| Problem Details errors | `docs/development/api-error-responses.md` |
| Authentication boundaries | `docs/development/authentication-boundary.md` |
| OpenAPI conventions | `docs/integrations/openapi.md` |
| MCP tool policy | `docs/integrations/mcp-tools.md`, `docs/explanation/why-mcp.md` |
| Database adapter boundaries | `docs/development/database-migrations.md` |
| Domain / use case layering | `docs/development/domain-layer.md` |

Install NENE2 as a Composer dependency and treat `vendor/hideyukimori/nene2/docs/` as the framework reference during development.

## Adapted for NeNe Records

| Topic | NeNe Records choice |
| --- | --- |
| Product goal | API-first flexible entity platform (not a general PHP framework) |
| Public Problem Details base URL | `https://nene-records.dev/problems/` (application errors) |
| NENE2 framework errors | Keep NENE2 canonical types when surfaced by the runtime |
| Coding standards | `docs/development/coding-standards.md` — NENE2 baseline + entity-platform additions |
| Language policy | English for public docs, OpenAPI, and API error metadata; Japanese allowed in Issues, PRs, commits, and `.cursor/rules/` |
| Review checklists | `docs/review/` — task-specific lists for this product |

## NeNe Records–specific (not inherited)

Record these in ADRs or product docs when they stabilize:

- Entity type / field schema model
- Typed value tables and soft-delete conventions
- Admin frontend vs consumer view boundaries
- Analytics and MCP tool catalog for entity operations
- Release versioning of the NeNe Records product (`v0.x` until first stable API)

## When upstream and local docs conflict

1. Update the **local source-of-truth doc** in this repository first.
2. If the conflict is about **framework behavior**, prefer NENE2 upstream unless an ADR documents a deliberate deviation.
3. Keep `.cursor/rules/` as a short summary; do not duplicate full policy text there.

## Verification commands (once runtime is scaffolded)

NeNe Records should expose the same quality gates as NENE2 consumer projects:

```bash
composer check
composer openapi
composer mcp
```

Until `composer.json` exists, governance changes are verified by doc review and `git diff --check`.
