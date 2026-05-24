# Current Work

Last updated: 2026-05-24

## Workspace

**Main Cursor workspace:** `/home/xi/docker/nene-records`

New contributors and AI agents: read [`handoff-2026-05-24-workspace-switch.md`](./handoff-2026-05-24-workspace-switch.md) and [`docs/explanation/product-vision.md`](../explanation/product-vision.md) first.

## In Progress

| Issue | Branch | Summary |
| --- | --- | --- |
| #14 | `feat/14-enum-bool-datetime-fields` | enum_fields / bool_fields / datetime_fields CRUD + field_defs data_type expansion |

## Up Next

| Issue | Summary |
| --- | --- |
| TBD | Phase 2: next typed field or registry follow-up |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #11 | int_fields CRUD + data_type=int (merged PR #12) |
| #9 | field_defs schema registry (merged PR #10, ADR 0002) |
| #1 | MVP entity CRUD vertical slice (merged PR #8) |
| #6 | Frontend/backend coding standards (merged PR #7) |
| #4 | Workspace handoff + product vision (merged PR #5) |
| #2 | NENE2 governance inheritance (merged PR #3) |

## Handoff Notes

- Phase 1 complete. Phase 2 in progress — registry (#9) done; typed field tables expanding.
- ADR 0002: `docs/adr/0002-entity-model-and-field-defs.md`
- Framework: `hideyukimori/nene2` via Composer path `../NENE2`

## Verification Commands

```bash
composer check
composer openapi
composer migrations:migrate   # requires .env with SQLite
```
