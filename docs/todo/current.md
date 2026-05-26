# Current Work

Last updated: 2026-05-27

## 状態サマリー

**M1 — Usable Blog CMS: 完了（2026-05-24）**

**M2 — Team-Ready CMS: 完了**

**M3 — Headless CMS Platform: 完了（2026-05-26）**

**M4 — 機能拡充＋使い勝手改善: 完了（2026-05-27）**

264 tests / PHPStan Max / CS-Fixer / OpenAPI / MCP バリデーション — 全グリーン。

---

## M4 完了（マージ済み）

| Issue | PR | Summary |
| --- | --- | --- |
| #145 | #148 | feat: Admin ダッシュボード（アクセス統計・公開数サマリー） |
| #146 | #149 | feat: API レート制限（IP ベース、120 req/60s） |
| #147 | #150 | feat: text_fields 多言語化（locale 列） |
| #151, #152 | #153 | feat: ステータスフィルター＋ロケール切替 UI |
| #154, #155 | #156 | feat: タグフィルター i18n・エクスポート URL・ページネーション改善 |
| #158 | #163 | refactor: サイドバーコンテンツ優先構造（Webhook を詳細設定へ） |
| #157 | #162 | refactor: WP 風用語統一（Content types / Items） |
| #159 | #164 | feat: entity_types に is_pinned フラグ追加 |
| #160 | #165 | feat: Posts・Pages デフォルトコンテンツタイプシード |
| #161 | #166 | feat: ピン留めタイプをサイドバーに動的表示・ダッシュボード改修 |
| #167 | #170 | feat: エンティティレコード管理 URL 短縮形 `/admin/:slug` |
| #168 | #171 | feat: 管理画面を `/admin/` 配下に移動・公開 URL から `/view/` 削除 |
| #172 | #173 | feat: デフォルトコンテンツタイプ多言語ラベルシード |
| #176 | #177 | feat: エンティティ一覧 body・作成/更新日時表示 |
| #174 | #175 | feat: エンティティ一覧ソート＋フィルターアコーディオン |
| — | #179 | feat: 管理画面フッター "Powered by NENE2 / © 2026 AYANE" |
| — | #181 | feat: フッター著作権を画面右下に固定表示 |
| — | #183 | feat: 管理画面テーマセレクター（5 テーマ） |
| — | #185 | feat: body フィールドを markdown 型に変更・Markdown エディター改善 |
| #186 | #187 | feat: 保存操作のトースト通知 |
| #169 | #188 | feat: WordPress 風パーマリンク設定（タイプ別 URL パターン・旧 URL リダイレクト） |

---

## 次フェーズ候補

| 項目 | 概要 | 難易度 |
| --- | --- | --- |
| AI Consumer Chat | NeNe Records を外部 API として使うチャットシステム（**別リポジトリ**） | 大 |
| コメント機能 | エンティティへのコメント投稿・管理 | 中 |
| メディアライブラリ | アップロード済みメディアの一覧・再利用 | 中 |
| 関連エンティティの公開表示 | Relation フィールドを公開ページでリンク表示 | 小 |

---

## M3 完了（マージ済み）

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

## Verification

```bash
composer check                    # 264 tests + PHPStan + CS-Fixer + OpenAPI + MCP
npm run check --prefix frontend   # type-check + lint + test + storybook
docker compose exec app vendor/bin/phinx migrate
docker compose exec app php tools/seed-blog-demo.php http://localhost
```
