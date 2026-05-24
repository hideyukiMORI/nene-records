# Current Work

Last updated: 2026-05-24

## Workspace

**Main Cursor workspace:** `/home/xi/docker/nene-records`

New contributors and AI agents: read [`handoff-2026-05-24-workspace-switch.md`](./handoff-2026-05-24-workspace-switch.md) and [`docs/explanation/product-vision.md`](../explanation/product-vision.md) first.

## In Progress

| Issue | Branch | Summary |
| --- | --- | --- |
| #16 | `feat/16-tags-and-entity-filters` | Tags CRUD, entity tag attach/detach/list, filtered entity list API |

## Up Next

| Issue | Summary |
| --- | --- |
| TBD | **Phase 3:** relations, search API extensions |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #14 | enum/bool/datetime fields CRUD (merged PR #15) — **Phase 2 complete** |
| #11 | int_fields CRUD (merged PR #12) |
| #9 | field_defs schema registry (merged PR #10, ADR 0002) |
| #1 | MVP entity CRUD vertical slice (merged PR #8) — Phase 1 |
| #6 | Frontend/backend coding standards (merged PR #7) |
| #4 | Workspace handoff + product vision (merged PR #5) |
| #2 | NENE2 governance inheritance (merged PR #3) |

## Handoff Notes

- **Phase 2 complete.** Typed fields: text, int, enum, bool, datetime + `field_defs` registry.
- **Issue #16 in progress:** `tags`, `entity_tags`, filtered `GET /entities`.
- ADR 0002: `docs/adr/0002-entity-model-and-field-defs.md`
- ADR 0003: `docs/adr/0003-tags-and-entity-tags.md`
- Phase 4 admin frontend not started; see `docs/development/frontend-standards.md`.

## Verification Commands

```bash
composer check
composer openapi
composer migrations:migrate   # requires .env with SQLite
```
