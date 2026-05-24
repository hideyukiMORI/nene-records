# Current Work

Last updated: 2026-05-24

## Up Next

| Issue | Summary |
| --- | --- |
| TBD | Records 一覧ラベル取得の API 最適化（entity_type 単位 batch 等） |

## In Progress

| Issue | Summary |
| --- | --- |
| #40 | field_def 編集（PUT）UI |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #38 | Consumer views — `/view/:slug` 一覧・詳細、PublicShell (PR #39) |
| #36 | Phase 4 完走 — enum/bool/datetime UI, entity type 編集, API entity_id filter, frontend CI (PR #37) |
| #34 | int_fields 値編集 UI (PR #35) |
| #32 | レコード一覧 title 表示 (PR #33) |
| #30 | text_fields 値編集 UI (PR #31) |
| #28 | field_defs UI (PR #29) |
| #26 | Entity records UI (PR #27) |
| #24 | Entity type CRUD UI (PR #25) |
| #20 | Admin React scaffold (PR #21) |

## Phase 4 Admin（ほぼ完了）

- Entity types（作成・一覧・編集・削除）
- Field defs（作成・一覧・削除 → **編集 #40 進行中**）
- Records（作成・一覧・削除・全型フィールド値編集）
- Records 一覧に title ラベル表示
- Consumer views（`/view/:slug`）
- CI: `.github/workflows/frontend-ci.yml`

## Verification

```bash
composer check                    # 128 tests
npm run check --prefix frontend   # 52 tests
```
