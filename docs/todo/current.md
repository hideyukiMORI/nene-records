# Current Work

Last updated: 2026-05-24

## 状態サマリー

**M1 — Usable Blog CMS: 完了（2026-05-24）**

178 tests / PHPStan Max / CS-Fixer / OpenAPI / MCP バリデーション — 全グリーン。
Backend CI + Frontend CI (GitHub Actions) 稼働中。

---

## M1 完了（2026-05-24）

| Issue | PR | Summary |
| --- | --- | --- |
| #78 | #79 | Consumer Markdown レンダリング |
| #84 | #85 | Publish status + `published_at` + Admin UI |
| #86 | #88 | Users + Admin ログイン + API auth |
| #87 | #89 | Public slug routing `/view/{type}/{slug}` |
| #90 | #99 #101 | PHP バックエンド GitHub Actions CI |
| — | #100 | fix: expires_at ISO 8601 バグ修正 |
| #91 | #102 | fix: datetime UTC タイムゾーン変換バグ修正 |
| #92 | #104 | perf: N+1 クエリ修正（EntityRelation + Setting） |
| #93 | #103 | feat: 401 → ログインリダイレクト |
| #94 | #103 | docs: CHANGELOG.md 追加 |
| #95 | #104 | feat: smithery.yaml 追加 |
| #96 | #103 | docs: llms.txt 追加 |
| #97 | #103 | docs: SECURITY.md 追加 |
| #98 | #103 | docs: README バッジ追加 |

---

## Up Next — M2（Team-Ready CMS）

| 優先 | 項目 | 説明 |
| --- | --- | --- |
| P0 | Roles（admin / editor） | capability check on mutating routes |
| P1 | Entity record revisions | API + Admin 履歴タブ（settings パターン踏襲） |
| P1 | Image / file field + upload API | 最小メディア対応 |
| P1 | Per-record SEO fields | meta override on public bootstrap |
| P2 | Navigation settings | menu defs + PublicShell |
| P2 | Admin Markdown editor UX | preview pane 付き textarea |

詳細は [`docs/milestones/2026-06-cms-mid-term.md`](../milestones/2026-06-cms-mid-term.md)。

---

## Verification

```bash
composer check                    # 178 tests + PHPStan + CS-Fixer + OpenAPI + MCP
npm run check --prefix frontend   # type-check + lint + test + storybook
docker compose exec app vendor/bin/phinx migrate
docker compose exec app php tools/seed-blog-demo.php http://localhost
```
