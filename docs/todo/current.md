# Current Work

Last updated: 2026-05-24

## Up Next

| Issue | Summary |
| --- | --- |
| — | （Phase 5 残: detachEntityRelation MCP — upstream 待ち） |

## In Progress

| Issue | Summary |
| --- | --- |
| — | （なし） |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #72 | Consumer Views Phase 6 強化 (PR #73) |
| #69 | Analytics / access log API (PR #70) |
| #67 | 全 API MCP カタログ 53 ツール (PR #68) |
| #65 | MCP relation tools カタログ (PR #66) |
| #63 | Consumer view relation リンク表示 (PR #64) |
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
- **Relations（済）:** Phase 3 一式 + MCP #65/#67
- **Phase 5（一部済）:** MCP 全 API カタログ（54 tools）、Analytics / access log (#69)
- **Phase 5 残:** detachEntityRelation MCP（upstream 待ち）
- **Phase 6（一部済）:** Consumer Views #38/#63/#72 — `/view` index, pagination, consumer theme, API 例

## Verification

```bash
composer check                    # 154 tests + openapi + mcp
npm run check --prefix frontend   # 68 tests
```
