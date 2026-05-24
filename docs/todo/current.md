# Current Work

Last updated: 2026-05-24

## Workspace

**Main Cursor workspace:** `/home/xi/docker/nene-records`

New contributors and AI agents: read [`handoff-2026-05-24-workspace-switch.md`](./handoff-2026-05-24-workspace-switch.md) and [`docs/explanation/product-vision.md`](../explanation/product-vision.md) first.

## In Progress

| Issue | Branch | Summary |
| --- | --- | --- |
| #26 | `feat/26-entity-records-ui` | Entity（Record）一覧・作成・削除 UI |

## Up Next

| Issue | Summary |
| --- | --- |
| TBD | Entity type 編集（PUT） |
| TBD | text_fields / field value 編集 UI |
| TBD | GitHub Actions frontend CI |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #24 | Entity type 作成・一覧・削除 UI (PR #25) |
| #22 | Docker compose — MySQL + phpMyAdmin darkwolf + Mailpit (PR #23) |
| #20 | Phase 4 Admin React scaffold (PR #21) |
| #18 | Frontend design system / Storybook policy (PR #19) |
| #16 | Tags, entity_tags, entity list filters (PR #17, ADR 0003) — **Phase 3 complete** |
| #14 | enum/bool/datetime fields (PR #15) — Phase 2 complete |
| #11 | int_fields CRUD (PR #12) |
| #9 | field_defs schema registry (PR #10, ADR 0002) |
| #1 | MVP entity CRUD (PR #8) — Phase 1 |

## Handoff Notes

- **Phase 3 complete.** Tags + entity tag attach/detach + filtered entity list with `total`.
- **127 tests** via `composer check`.
- ADRs: 0002 (field defs), 0003 (tags).
- **Docker:** `docker compose up --build` → API `:8080`, phpMyAdmin `:8081`, Mailpit `:8025`. See `docs/development/docker.md`.
- **Phase 4:** `frontend/` — entity types + entity records admin UI; route `/entity-types/:entityTypeId/entities`.
- Run admin UI: `npm run dev --prefix frontend` (API on :8080 via Docker or PHP built-in).

## Verification Commands

```bash
composer check
composer openapi
docker compose up --build
curl http://localhost:8080/health
npm run check --prefix frontend
```

## Example queries (Phase 3)

```bash
# Entities for type 1 tagged php or api
curl 'http://localhost:8080/api/v1/entities?entity_type_id=1&tags=php,api'

# Attach tag 2 to entity 5
curl -X POST http://localhost:8080/api/v1/entities/5/tags \
  -H 'Content-Type: application/json' -d '{"tag_id":2}'
```
