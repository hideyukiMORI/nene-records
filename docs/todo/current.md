# Current Work

Last updated: 2026-05-26

## 状態サマリー

**M1 — Usable Blog CMS: 完了（2026-05-24）**

**M2 — Team-Ready CMS: 完了** — P1/P2 バックログ全消化

**M3 — P1 完了・P2 進行中** — 全文検索 API（#131）・Admin UI リデザイン＋レスポンシブ（#128）・エクスポート API（#132 マージ済み）・ファイルフィールド（#134 マージ済み）・Webhook（#136 レビュー中）

229 tests / PHPStan Max / CS-Fixer / OpenAPI / MCP バリデーション — 全グリーン。
Backend CI + Frontend CI (GitHub Actions) 稼働中。

---

## M3 進捗

| 優先 | 項目 | Issue | PR | 状態 |
| --- | --- | --- | --- | --- |
| P1 | Admin UI リデザイン＋レスポンシブ | #127 | #128 | ✅ マージ済み |
| P1 | Full-text search API | #129 | #131 | ✅ マージ済み |
| P1 | Export API (CSV/JSON) | #130 | #132 | ✅ マージ済み |
| P2 | File field + upload | #133 | #134 | ✅ マージ済み |
| P2 | Webhook / event hooks | #135 | #136 | 🔄 レビュー中 |

---

## M2 完了（マージ済み）

| Issue | PR | Summary |
| --- | --- | --- |
| — | #106 | fix: entity type 削除時の 500（存在チェック + UI エラー表示） |
| — | #107 | feat: entity type 削除前のアーカイブ + CSV ダウンロード |
| #108 | #111 | feat: admin / editor ロール + capability チェック |
| #113 | #114 | refactor: PHP/TypeScript 命名規則ドキュメント + 全ファイル整合 |
| #109 | #115 | feat: Admin i18n 基盤（6 言語メッセージカタログ・`t()`・locale 切替） |
| #110 | #116 | feat: Admin 全画面 i18n 移行（ハードコード排除） |
| #117 | #118 | feat: エンティティレコード変更履歴（revisions） |
| #119 | #120 | feat: エンティティレコードごとの SEO フィールド（meta_title / meta_description） |
| #121 | #122 | feat: 画像フィールド + メディアアップロード API（+ Vite proxy バグ修正） |
| #124 | #123 | feat: Markdown フィールド型 + 管理 UI プレビューエディタ |
| #125 | #124 | feat: ナビゲーション設定 CRUD API とフロントエンド管理画面 |

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

## M3 P2 完了（マージ済み / レビュー中）

| Issue | PR | Summary |
| --- | --- | --- |
| #133 | #134 | feat: ファイルフィールド + image 以外のメディアアップロード対応 |
| #135 | #136 | feat: Webhook / イベントフック（エンティティ変更時の外部通知） |

## Up Next — M3 P3（検討中）

| 優先 | 項目 | 説明 |
| --- | --- | --- |
| — | Scheduled publish | 公開日時指定・自動公開 |
| — | Multi-language content | フィールド値の多言語化 |
| — | Rate limiting | API レート制限 |

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
composer check                    # 229 tests + PHPStan + CS-Fixer + OpenAPI + MCP
npm run check --prefix frontend   # type-check + lint + test + storybook
docker compose exec app vendor/bin/phinx migrate
docker compose exec app php tools/seed-blog-demo.php http://localhost
```
