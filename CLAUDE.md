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
| Current tasks | private: `nene-origin/internal-docs/records/todo/current.md` |
| Roadmap | `docs/roadmap.md` |

## Quick Rules

- **Issue-driven**: no Issue, no code/doc change (except explicit user scope limits).
- **Branch**: `type/issue-number-summary` from `main`; never commit directly to `main`.
- **Commits**: Conventional Commits; type/scope English, description/body Japanese, include `(#issue)`.
- **PR**: purpose, changes, verification, checklist name, `Closes #n`.
- **Secrets**: never commit `.env`, tokens, or credentials.
- **Daily reports**: private `nene-origin/internal-docs/records/daily/YYYY-MM-DD.md`（日報規約 v3 §11・2026-07-18 移設。公開リポには置かない）.
- **design-export/**: local claudeDesign working directory — keep it untracked (kept out of git via local `.git/info/exclude`, not the shared `.gitignore`); never `git add` it.
- **Framework**: NENE2 via Composer — read `vendor/hideyukimori/nene2/docs/` for runtime patterns.
- **MCP**: tools go through OpenAPI HTTP boundary only.

## Operational logs (moved)

> 運用ログ（`docs/todo`・`docs/daily`・field-trials 相当）は private
> `nene-origin/internal-docs/records/` に移設済み。最新の作業状況・申し送りはそちらを読むこと。

## Product Direction

Flexible entity platform — lighter and more typed than WordPress post meta. Admin frontend for schema UX; API for persistence, auth, and contracts; MCP for AI operations. Multi-tenant: organization-scoped data with JWT `org_id` enforcement.

## Current Status

M1 – M16 complete plus the post-#536 wave (crawlable SSR / WXR migration / public i18n / production SaaS + Tier A zip installer, v0.5.2 released). In production as a subdomain multi-tenant SaaS. The moving frontier is intentionally not tracked here — the top of the private current.md (`nene-origin/internal-docs/records/todo/`) is the source of truth.

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

| Service | URL | env var |
| --- | --- | --- |
| API | http://localhost:18082 | `NENE_RECORDS_PORT` |
| phpMyAdmin | http://localhost:18083 | `NENE_RECORDS_PHPMYADMIN_PORT` |
| Mailpit | http://localhost:18026 | `NENE_RECORDS_MAILPIT_WEB_PORT` |
| MySQL (host) | localhost:13308 | `NENE_RECORDS_MYSQL_PORT` |
| Frontend dev | http://localhost:18084 | `NENE_RECORDS_FRONTEND_PORT` |

Health check: `curl -fsS http://localhost:18082/health`

Frontend dev: `npm run dev --prefix frontend` → http://localhost:18084

### Port assignment rule

NeNe Records uses the **180xx / 133xx** range to avoid conflicts with other local stacks:

| Range | Owner |
| --- | --- |
| 83xx | NeNeClear |
| 180xx + 133xx | NeNe Records (this project) |

Do not revert ports to 80xx defaults. Set them in `.env` (committed values live in `.env.example` comments).

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

## Release

1. `VERSION` をバンプする（版の単一ソース。`/machine/health` も同じ値を報告する・#586）。
2. `bash tools/build-release.sh <version>` で配布 zip を作る（→ `dist/nene-records-<version>.zip`。詳細は `docs/install-tier-a.md`）。
3. zip と `.sha256` を GitHub Release に添付して公開する（Latest・target=main）。
4. Release 公開後、プロフィール README（github.com/hideyukiMORI/hideyukiMORI）の版数・状況表を同期する（At a glance / Platform products / What shipped recently の該当行）。

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
