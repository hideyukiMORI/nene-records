# Current Work

Last updated: 2026-05-24

## Up Next

| Issue | Summary |
| --- | --- |
| TBD | Record への tag attach/detach UI |
| TBD | Records 一覧 tag フィルタ UI |
| TBD | entity-to-entity relations（要設計） |

## In Progress

| Issue | Summary |
| --- | --- |
| #44 | Tag CRUD Admin UI（Phase 3 開始） |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #42 | text-fields entity_type_id フィルタ + 一覧ラベル最適化 (PR #43) |
| #40 | field_def 編集（PUT）UI (PR #41) |
| #38 | Consumer views (PR #39) |
| #36 | Phase 4 完走 (PR #37) |

## Phase 3 Tags / Query

- **API（済）:** tags CRUD, entity_tags attach/detach, entities `?tags=` フィルタ
- **Admin UI（進行中）:** Tag CRUD (#44)
- **未着手:** Record tag 管理、一覧フィルタ、relations モデル

## Verification

```bash
composer check                    # 130 tests
npm run check --prefix frontend   # 56 tests
```
