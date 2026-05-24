# Current Work

Last updated: 2026-05-24

## Up Next

| Issue | Summary |
| --- | --- |
| — | Consumer view relation links |

## In Progress

| Issue | Summary |
| --- | --- |
| — | （なし） |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #61 | Record 詳細 inverse relation 参照元一覧 UI (PR #62) |
| #59 | Records 一覧 relation フィルタ UI (PR #60) |
| #57 | entities 一覧 relation フィルタ API (PR #58) |
| #52 | Record 詳細 relation attach/detach UI |
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
- **Relations（済）:** API #51, Admin UI #52, list filter #57/#59, inverse UI #61
- **Relations 後続:** Consumer view relation links, MCP

## Verification

```bash
composer check                    # 144 tests
npm run check --prefix frontend   # 64 tests
```
