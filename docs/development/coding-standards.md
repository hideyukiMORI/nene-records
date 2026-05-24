# Coding Standards

NeNe Records coding standards split by surface. **Full policies live in the dedicated documents below** — this file is the index.

| Surface | Source of truth |
| --- | --- |
| **PHP / API / database** | [`backend-standards.md`](./backend-standards.md) |
| **React / TypeScript admin & consumer** | [`frontend-standards.md`](./frontend-standards.md) |
| **NENE2 inheritance map** | [`../inheritance-from-nene2.md`](../inheritance-from-nene2.md) |

**Framework baseline:** [NENE2 coding standards](https://github.com/hideyukiMORI/NENE2/blob/main/docs/development/coding-standards.md) — NeNe Records deviates only where local docs or ADRs say so.

---

## Shared rules (all surfaces)

- GitHub Issue-driven work; focused PRs; no direct commits to `main`
- **Strict typing** at boundaries — PHP readonly DTOs; TypeScript strict mode
- **OpenAPI** is the public API contract; MCP maps to the same HTTP operations
- Application Problem Details `type`: `https://nene-records.dev/problems/{problem-name}`
- **Placement violations block merge** — see backend and frontend standards
- Public docs, OpenAPI text, and API error metadata: **English**
- Issues, PRs, commits, `.cursor/rules/`: **Japanese allowed**

---

## Backend (summary)

Full policy: **`docs/development/backend-standards.md`**.

- NENE2 consumer — framework in `vendor/`, product in `src/`
- Domain-grouped modules (`EntityType/`, `Entity/`, …) — not layer folders
- Handler → UseCase → RepositoryInterface → PdoRepository
- No PDO/SQL outside `Pdo*Repository`; no business logic in handlers
- Phinx migrations + `database/schema/` snapshots; soft delete conventions
- PHPUnit: in-memory use cases, SQLite repositories, OpenAPI contract tests
- `composer check` before merge

---

## Frontend (summary)

Full policy: **`docs/development/frontend-standards.md`**.

- Phase 4+ implementation; Issue `#1` is API-only
- React + TypeScript strict + TanStack Query + zero-tolerance module placement
- API first — schema validation at API, not browser alone

---

## Verification (once scaffolded)

```bash
composer check
composer openapi
composer mcp
npm run check --prefix frontend   # when frontend/ exists
```

Until `composer.json` exists, governance and standards docs are verified by review and `git diff --check`.
