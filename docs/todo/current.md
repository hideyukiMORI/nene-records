# Current Work

Last updated: 2026-05-24

## Up Next

| Issue | Summary |
| --- | --- |
| TBD | Records 一覧 tag フィルタ UI |
| TBD | entity-to-entity relations（要設計） |

## In Progress

| Issue | Summary |
| --- | --- |
| #46 | Record への tag attach/detach UI |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #44 | Tag CRUD Admin UI (PR #45) |
| #42 | text-fields entity_type_id フィルタ (PR #43) |
| #40 | field_def 編集 UI (PR #41) |
| #38 | Consumer views (PR #39) |

## Phase 3 Tags / Query

- **API（済）:** tags CRUD, entity_tags, entities `?tags=` フィルタ
- **Admin UI（済）:** Tag CRUD (#44)
- **Admin UI（進行中）:** Record tag attach/detach (#46)
- **未着手:** Records 一覧フィルタ、relations モデル

## Verification

```bash
composer check                    # 130 tests
npm run check --prefix frontend   # 57 tests
```
