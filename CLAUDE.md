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
composer check                    # 374 tests + PHPStan level 8 + CS-Fixer + OpenAPI + MCP
npm run check --prefix frontend   # type-check + lint + test + storybook
```
