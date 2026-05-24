# Current Work

Last updated: 2026-05-24

## Workspace

**Main Cursor workspace:** `/home/xi/docker/nene-records`

New contributors and AI agents: read [`handoff-2026-05-24-workspace-switch.md`](./handoff-2026-05-24-workspace-switch.md) and [`docs/explanation/product-vision.md`](../explanation/product-vision.md) first.

## In Progress

| Issue | Branch | Summary |
| --- | --- | --- |
| — | — | _(none — Phase 4 planning)_ |

## Up Next

| Issue | Summary |
| --- | --- |
| TBD | **Phase 4:** Admin React frontend scaffold |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #16 | Tags, entity_tags, entity list filters (merged PR #17, ADR 0003) — **Phase 3 complete** |
| #14 | enum/bool/datetime fields (merged PR #15) — Phase 2 complete |
| #11 | int_fields CRUD (merged PR #12) |
| #9 | field_defs schema registry (merged PR #10, ADR 0002) |
| #1 | MVP entity CRUD (merged PR #8) — Phase 1 |

## Handoff Notes

- **Phase 3 complete.** Tags + entity tag attach/detach + filtered entity list with `total`.
- **127 tests** via `composer check`.
- ADRs: 0002 (field defs), 0003 (tags).
- Phase 4 admin UI: see `docs/development/frontend-standards.md`.

## Verification Commands

```bash
composer check
composer openapi
composer migrations:migrate   # requires .env with SQLite
```

## Example queries (Phase 3)

```bash
# Entities for type 1 tagged php or api
curl 'http://localhost:8080/api/v1/entities?entity_type_id=1&tags=php,api'

# Attach tag 2 to entity 5
curl -X POST http://localhost:8080/api/v1/entities/5/tags \
  -H 'Content-Type: application/json' -d '{"tag_id":2}'
```
