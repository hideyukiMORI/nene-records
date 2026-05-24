# Current Work

Last updated: 2026-05-24

## Workspace

**Main Cursor workspace:** `/home/xi/docker/nene-records`

New contributors and AI agents: read [`handoff-2026-05-24-workspace-switch.md`](./handoff-2026-05-24-workspace-switch.md) and [`docs/explanation/product-vision.md`](../explanation/product-vision.md) first.

## In Progress

| Issue | Branch | Summary |
| --- | --- | --- |
| #30 | `feat/30-text-fields-ui` | text_fields 値編集 UI（レコード詳細） |

## Up Next

| Issue | Summary |
| --- | --- |
| TBD | int/enum/bool/datetime フィールド値 UI |
| TBD | Entity type 編集（PUT） |
| TBD | API: text-fields list filter by entity_id |
| TBD | GitHub Actions frontend CI |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #28 | field_defs 一覧・作成・削除 UI (PR #29) |
| #26 | Entity（Record）一覧・作成・削除 UI (PR #27) |
| #24 | Entity type 作成・一覧・削除 UI (PR #25) |
| #22 | Docker compose (PR #23) |
| #20 | Phase 4 Admin React scaffold (PR #21) |
| #18 | Frontend design system / Storybook (PR #19) |
| #16 | Tags + entity list filters (PR #17) — **Phase 3 complete** |

## Handoff Notes

- **Phase 4:** entity types → field defs → records → text field values admin flow.
- text-fields list has no `entity_id` filter yet; frontend filters client-side (limit 100).
- Run admin UI: `npm run dev --prefix frontend` + Docker API on :8080.

## Verification Commands

```bash
composer check
npm run check --prefix frontend
docker compose up --build
curl http://localhost:8080/health
```
