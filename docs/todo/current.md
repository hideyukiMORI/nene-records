# Current Work

Last updated: 2026-05-24

## Up Next

| Issue | Summary |
| --- | --- |
| — | （なし） |

## In Progress

| Issue | Summary |
| --- | --- |
| #52 | Record 詳細 relation attach/detach UI |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #51 | entity relations API + field_defs relation 型 (PR #54) |
| #48 | Records 一覧 tag フィルタ UI (PR #49) |
| #46 | Record tag attach/detach UI (PR #47) |
| #44 | Tag CRUD Admin UI (PR #45) |
| #42 | text-fields entity_type_id フィルタ (PR #43) |
| #40 | field_def 編集 UI (PR #41) |
| #38 | Consumer views (PR #39) |

## Phase 3 Tags / Query / Relations

- **API（済）:** tags CRUD, entity_tags, entities `?tags=` フィルタ
- **Admin UI（済）:** Tag CRUD (#44), Record tag attach/detach (#46), Records 一覧 tag フィルタ (#48)
- **Relations（進行中）:** Admin UI #52
- **Relations API（済）:** field_defs relation 型、entity_relations、attach/detach/list (#51)
- **Relations 後続:** list filter `?relation.{field_key}=`, inverse, MCP

## Verification

```bash
composer check                    # 137 tests
npm run check --prefix frontend   # 59 tests
```
