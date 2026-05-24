# Current Work

Last updated: 2026-05-24

## Up Next

| Issue | Summary |
| --- | --- |
| TBD | （次フェーズ: Relations / MCP 等 — Issue 起票待ち） |

## In Progress

| Issue | Summary |
| --- | --- |
| #42 | text-fields `entity_type_id` フィルタ + 一覧ラベル取得最適化 |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #40 | field_def 編集（PUT）UI (PR #41) |
| #38 | Consumer views — `/view/:slug` 一覧・詳細、PublicShell (PR #39) |
| #36 | Phase 4 完走 — enum/bool/datetime UI, entity type 編集, API entity_id filter, frontend CI (PR #37) |
| #34 | int_fields 値編集 UI (PR #35) |
| #32 | レコード一覧 title 表示 (PR #33) |
| #30 | text_fields 値編集 UI (PR #31) |
| #28 | field_defs UI (PR #29) |
| #26 | Entity records UI (PR #27) |
| #24 | Entity type CRUD UI (PR #25) |
| #20 | Admin React scaffold (PR #21) |

## Phase 4 Admin（完了）

- Entity types（作成・一覧・編集・削除）
- Field defs（作成・一覧・編集・削除）
- Records（作成・一覧・削除・全型フィールド値編集）
- Records 一覧に title ラベル表示（entity type スコープ fetch）
- Consumer views（`/view/:slug`）
- CI: `.github/workflows/frontend-ci.yml` + `frontend/.npmrc`

## Verification

```bash
composer check                    # 130 tests
npm run check --prefix frontend   # 52 tests
```
