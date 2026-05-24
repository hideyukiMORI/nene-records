# Current Work

Last updated: 2026-05-25

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

| 優先 | Issue | 項目 | 説明 |
| --- | --- | --- | --- |
| **P0** | [#108](https://github.com/hideyukiMORI/nene-records/issues/108) | Roles（admin / editor） | capability check — **実装中** (`feat/108-roles`) |
| **P0.5** | [#109](https://github.com/hideyukiMORI/nene-records/issues/109) | Admin i18n 基盤 | 13 言語メッセージカタログ・`t()`・locale 切替（#108 後） |
| **P0.5** | [#110](https://github.com/hideyukiMORI/nene-records/issues/110) | Admin 全画面 i18n 移行 | ハードコード排除・13 locale 完全キー一致（#109 後） |
| P1 | — | Entity record revisions | API + Admin 履歴タブ（settings パターン踏襲） |
| P1 | — | Image / file field + upload API | 最小メディア対応 |
| P1 | — | Per-record SEO fields | meta override on public bootstrap |
| P2 | — | Navigation settings | menu defs + PublicShell |
| P2 | — | Admin Markdown editor UX | preview pane 付き textarea |

**Admin i18n 13 言語:** NENE2 VitePress 既存 6 locale（en, ja, fr, zh, pt-br, de）+ es, ko, it, ru, ar, hi, vi。API / Problem Details は NENE2 language policy どおり英語維持。

詳細は [`docs/milestones/2026-06-cms-mid-term.md`](../milestones/2026-06-cms-mid-term.md)。

---

---

## Ideas — Beyond M3

将来のフェーズで検討するアイデア。Issue 未起票。

### AI Consumer Chat（DB 問い合わせチャットシステム）

> **⚠️ 設計原則: NeNe Records とは完全に分離した独立システムとして開発すること。**
> この Chat System を NeNe Records に統合してはならない。

自社の NeNe Records DB に対して自然言語で問い合わせ、回答を生成するコンシューマー向けチャット。

#### なぜ分離か

依存の方向が「Chat → NeNe Records API」であり、Chat は NeNe Records の **クライアント** である。クライアントをサーバーに同居させることは依存の方向を逆転させる。

| 関心事 | NeNe Records | Chat System |
| --- | --- | --- |
| 役割 | コンテンツ管理 | 会話・推論・回答生成 |
| 利用者 | 管理者・編集者 | エンドコンシューマー |
| 認証 | JWT（管理者向け） | 匿名 or 別セッション |
| 通信 | REST JSON | SSE / WebSocket が自然 |
| 障害分離 | Chat が落ちても CMS は動く | NeNe が遅くても Chat はキャッシュで逃げられる |

NENE2 を基盤とした **別の独立クライアントプロジェクト** として立てる。

```
NENE2（フレームワーク）
  ├── NeNe Records（CMS）        ← ここ
  └── Chat System（独立）        ← 別リポジトリで開発
```

#### アーキテクチャ

```
Consumer Chat UI
    ↓
Chat API（レート制限・認証・トークン計測）← Chat System の責務
    ↓
Claude API（tool_use / function calling）
    ↓
NeNe Records 読み取り専用 API           ← NeNe Records は API を提供するだけ
    ↓
DB
```

MCP プロトコル自体はコンシューマーに見せない。Claude が tool_use で NeNe Records の既存 API を叩く形。

#### 着手タイミング

NeNe Records M3（full-text search / export API）が成熟してから。Chat が必要とする「軽量な検索 API」が M3 で揃う。

#### 検討が必要な課題
- スコープ管理: DB で答えられない質問へのフォールバック
- プロンプトインジェクション対策
- レート制限の粒度（ユーザーごと推奨）
- tool_use のレイテンシ（2〜3 回 DB 呼び出し = 5〜10 秒）

---

## Verification

```bash
composer check                    # 178 tests + PHPStan + CS-Fixer + OpenAPI + MCP
npm run check --prefix frontend   # type-check + lint + test + storybook
docker compose exec app vendor/bin/phinx migrate
docker compose exec app php tools/seed-blog-demo.php http://localhost
```
