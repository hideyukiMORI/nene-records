# Current Work

Last updated: 2026-05-26

## 状態サマリー

**M1 — Usable Blog CMS: 完了（2026-05-24）**

**M2 — Team-Ready CMS: 完了** — P1/P2 バックログ全消化

**M3 — Headless CMS Platform: 完了（2026-05-26）** — 全 P1〜P3 main マージ済み

253 tests / PHPStan Max / CS-Fixer / OpenAPI / MCP バリデーション — 全グリーン。
Backend CI + Frontend CI (GitHub Actions) 稼働中。

---

## M3 完了（全マージ済み）

| 優先 | 項目 | Issue | PR |
| --- | --- | --- | --- |
| P1 | Admin UI リデザイン＋レスポンシブ | #127 | #128 |
| P1 | Full-text search API | #129 | #131 |
| P1 | Export API (CSV/JSON) | #130 | #132 |
| P2 | File field + upload | #133 | #134 |
| P2 | Webhook / event hooks | #135 | #136 |
| P3 | Scheduled publish | #137 | #138 |
| P3 | Draft preview token | #139 | #141 |
| P3 | Public cache / ETag | #142 | #143 |

---

## M2 完了（マージ済み）

| Issue | PR | Summary |
| --- | --- | --- |
| — | #106 | fix: entity type 削除時の 500 |
| — | #107 | feat: entity type アーカイブ + CSV |
| #108 | #111 | feat: admin / editor ロール |
| #113 | #114 | refactor: 命名規則整合 |
| #109 | #115 | feat: Admin i18n 基盤 |
| #110 | #116 | feat: Admin 全画面 i18n 移行 |
| #117 | #118 | feat: entity record revisions |
| #119 | #120 | feat: per-record SEO fields |
| #121 | #122 | feat: 画像フィールド + upload API |
| #124 | #123 | feat: Markdown フィールド |
| #125 | #124 | feat: ナビゲーション設定 |

---

## Up Next — M4 候補

Issue 未起票。優先度未定。

| 項目 | 概要 | 難易度 |
| --- | --- | --- |
| 予約公開 cron 実装 | `ProcessScheduledPublish` を定期実行（Docker cron） | 小 |
| CHANGELOG 更新 | M2〜M3 全 PR を CHANGELOG.md に追記 | 小 |
| Multi-language content | フィールド値の多言語化 | 大 |
| Rate limiting | API レート制限（IP / JWT ベース） | 中 |
| AI Consumer Chat | NeNe Records を外部 API として使うチャットシステム（**別リポジトリ**） | 大 |
| Admin ダッシュボード | アクセス数・公開数サマリー画面 | 中 |

---

## Verification

```bash
composer check                    # 253 tests + PHPStan + CS-Fixer + OpenAPI + MCP
npm run check --prefix frontend   # type-check + lint + test + storybook
docker compose exec app vendor/bin/phinx migrate
docker compose exec app php tools/seed-blog-demo.php http://localhost
```
