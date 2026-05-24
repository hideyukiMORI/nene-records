# Current Work

Last updated: 2026-05-24

## Workspace

**Main Cursor workspace:** `/home/xi/docker/nene-records`

New contributors and AI agents: read [`handoff-2026-05-24-workspace-switch.md`](./handoff-2026-05-24-workspace-switch.md) and [`docs/explanation/product-vision.md`](../explanation/product-vision.md) first.

## In Progress

| Issue | Branch | Summary |
| --- | --- | --- |
| #9 | `feat/9-field-defs-registry` | Phase 2: `field_defs` schema registry API |

## Up Next

| Issue | Summary |
| --- | --- |
| — | Phase 2 follow-up: int / enum / bool / datetime field types |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #1 | MVP entity CRUD vertical slice (merged PR #8) |
| #6 | Frontend/backend coding standards (merged PR #7) |
| #4 | Workspace handoff + product vision (merged PR #5) |
| #2 | NENE2 governance inheritance (merged PR #3) |
| — | Repository bootstrap (`README`, MIT license) |

## Handoff Notes

- **Phase 1 complete.** `composer check`, `composer openapi` available.
- Phase 2 starts with ADR 0002 + `field_defs` registry (Issue #9).
- Framework reference: `hideyukimori/nene2` via Composer path `../NENE2`; see `docs/inheritance-from-nene2.md`.

## Verification Commands

```bash
composer check
composer openapi
composer migrations:migrate   # requires .env with SQLite
```
