# Current Work

Last updated: 2026-05-24

## M1 完了（2026-05-24）

| Issue | PR | Summary |
| --- | --- | --- |
| #78 | #79 | Consumer Markdown レンダリング |
| #84 | #85 | Publish status + `published_at` + Admin UI |
| #86 | #88 | Users + Admin ログイン + API auth |
| #87 | #89 | Public slug routing `/view/{type}/{slug}` |

## Up Next（M1 補完 / M2 準備）

| 優先 | Issue | 項目 | 備考 |
| --- | --- | --- | --- |
| P0 | #90 | PHP バックエンド GitHub Actions CI | 最も閉じやすいギャップ |
| P0 | #93 | フロントエンド 401 → ログインリダイレクト | 認証統合の最後のピース |
| P1 | #91 | datetime 表示タイムゾーン変換バグ | エンドユーザーに見える |
| P2 | #92 | N+1 クエリ最適化（relation + settings） | スケール前に対処 |
| P2 | — | Admin Markdown エディタ UX（preview 付き） | M2 |
| P2 | — | Per-record SEO（meta override on bootstrap） | M2 |
| P3 | — | Entity record revisions（settings パターン） | M2 |
| P3 | — | Image/file field + upload API + media UI | M3 |
| P3 | — | Full-text search API | M3 |
| P3 | — | Scheduled publish / webhooks / import-export | M3 |

Issues #90〜#93 は `docs/claude-impressions.md` の指摘から起票。

## Recently Completed

| Issue | PR | Summary |
| --- | --- | --- |
| #87 | #89 | Public slug routing |
| #86 | #88 | Users + Admin login + API auth |
| #84 | #85 | Publish status + published_at |
| #82 | #83 | CMS 中期ロードマップ docs |
| #78 | #79 | Consumer Markdown レンダリング |
| #80 | #81 | Site Settings |
| #72 | #73 | Consumer Views Phase 6 強化 |

## Verification

```bash
composer check                    # 168 tests + openapi + mcp（PHP CI は #90 で追加予定）
npm run check --prefix frontend   # type-check + lint + test + storybook
docker compose exec app vendor/bin/phinx migrate
```
