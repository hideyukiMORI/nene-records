# CLAUDE.md — NeNe Records

Claude Code / AI agent guide for this repository. Cursor summaries live in `.cursor/rules/`.

## Source of Truth

| Purpose | Document |
| --- | --- |
| NENE2 inheritance | `docs/inheritance-from-nene2.md` |
| Agent entry | `AGENTS.md` |
| Workflow | `docs/workflow.md` |
| Commits | `docs/development/commit-conventions.md` |
| Coding | `docs/development/coding-standards.md` |
| Current tasks | `docs/todo/current.md` |
| Roadmap | `docs/roadmap.md` |

## Quick Rules

- **Issue-driven**: no Issue, no code/doc change (except explicit user scope limits).
- **Branch**: `type/issue-number-summary` from `main`; never commit directly to `main`.
- **Commits**: Conventional Commits; type/scope English, description/body Japanese, include `(#issue)`.
- **PR**: purpose, changes, verification, checklist name, `Closes #n`.
- **Secrets**: never commit `.env`, tokens, or credentials.
- **Framework**: NENE2 via Composer — read `vendor/hideyukimori/nene2/docs/` for runtime patterns.
- **MCP**: tools go through OpenAPI HTTP boundary only.

## Product Direction

Flexible entity platform — lighter and more typed than WordPress post meta. Admin frontend for schema UX; API for persistence, auth, and contracts; MCP for AI operations. Multi-tenant: organization-scoped data with JWT `org_id` enforcement.

## Current Status

M1 – M9 complete. See `docs/todo/current.md`.

## Verification

```bash
# Full quality gates (run before every PR)
composer check                    # PHPUnit + PHPStan level 8 + CS-Fixer + OpenAPI + MCP
npm run check --prefix frontend   # type-check + lint + prettier + test + storybook

# Individual steps (faster feedback during development)
composer test                     # PHPUnit only
composer analyse                  # PHPStan only
composer cs                       # CS-Fixer dry-run
composer openapi                  # OpenAPI contract validation
npm run type-check --prefix frontend
npm run test --prefix frontend
```

## Local stack

| Service | URL |
| --- | --- |
| API | http://localhost:8082 |
| phpMyAdmin | http://localhost:8083 |
| Mailpit | http://localhost:8026 |
| Frontend dev | http://localhost:5173 |

Health check: `curl -fsS http://localhost:8082/health`

## Database migrations

**Create a new migration** (auto-assigns the next safe version number):
```bash
composer migrations:new -- CreateFooTable
# → database/migrations/YYYYMMDD######_create_foo_table.php
```

Never create migration files manually — version collisions cause silent skips.

```bash
composer migrations:status    # show applied / pending
composer migrations:migrate   # apply pending
composer migrations:rollback  # rollback last batch
```

## OpenAPI → TypeScript types

The frontend types for API responses are **auto-generated** from `docs/openapi/openapi.yaml`.

```bash
npm run codegen --prefix frontend
# → frontend/src/shared/api/schema.gen.ts (do not edit manually)
```

When you add or change an endpoint:
1. Update `docs/openapi/openapi.yaml`
2. Run `npm run codegen --prefix frontend`
3. Reference types via `components['schemas']['YourSchema']` in the entity's `api-types.ts`

Example pattern (`src/entities/user/api-types.ts`):
```ts
import type { components } from '@/shared/api/schema.gen'
export type UserDto = components['schemas']['UserResponse']
```
