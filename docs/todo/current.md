# Current Work

Last updated: 2026-06-13

## 状態サマリー

**M1 — Usable Blog CMS: 完了（2026-05-24）**

**M2 — Team-Ready CMS: 完了**

**M3 — Headless CMS Platform: 完了（2026-05-26）**

**M4 — 機能拡充＋使い勝手改善: 完了（2026-05-27）**

**M5 — ユーザー管理・メディアライブラリ: 完了（2026-05-27）**

**M6 — コメント機能: 完了（2026-05-27）**

**M7 — マルチテナント基盤・スーパー管理画面: 完了（2026-05-27）**

**M8 — マルチテナント運用機能: 完了（2026-05-27）**

**M9 — マルチテナント完成（1ユーザー=1組織）: 完了（2026-07-01）**

**M10 — マルチテナント包括テストスイート + E2E 拡充: 完了（2026-05-27）**

**M11 — 通知・運用機能の拡充: 完了（2026-05-28）**

**M12 — メディアライブラリ強化（ストレージ抽象化・派生画像）: 完了（2026-06-12）**

**M13 — 管理コンソール再設計: 完了（2026-06-12）**

**M14 — 公開レイアウト & Custom Page: 完了（2026-06-12）**

**M15 — ウィジェット（Epic #324・全フェーズ）: 完了（2026-06-13）**

最新の検証基準: PHPUnit 681 tests / PHPStan level 8 / CS-Fixer / OpenAPI / MCP ＋
`npm run check`（type-check / lint / prettier / test / knip / storybook）全グリーン。

---

## M15 — ウィジェット（Epic #324）: 完了（2026-06-13）

サイドバー/アサイド領域に置ける型付き・固定種類のウィジェット群（Elementor 路線は不採用）。

| Issue | PR | Summary |
| --- | --- | --- |
| #325 | #326 | feat: ウィジェット基盤 + 最近の投稿（region 配置・公開描画） |
| #327 | #328 | feat: メニューの location(header/footer/side) + メニューウィジェット |
| #331 | #332 | feat: 目次(TOC) — 見出しアンカー + 目次ウィジェット |
| #333 | #334 | feat: インライン目次（単一カラムの本文先頭に自動配置） |
| #335 | #336 | feat: サイト内検索（検索ウィジェット + `/search` 結果ページ） |
| #337 | #338 | feat: タグクラウド + タグアーカイブ `/tag/:slug` |
| #339 | #340 | feat: 人気記事 + アクセス集計API（`/api/v1/analytics/popular-entities`） |
| #341 | #342 | feat: カレンダー + 日付アーカイブ `/archive/:y/:m(/:d)` |
| #329 | #330 | chore(db): NENE2 サンプル `notes` テーブルを削除 |

**提供ウィジェット:** `recent-posts` / `menu` / `toc` / `search` / `tag-cloud` / `popular-posts` / `calendar`
**公開ページ追加:** `/search`、`/tag/:slug`、`/archive/:year/:month(/:day)`
**基盤追加:** entities 一覧に公開日レンジ絞り込み（`published_from`/`published_to`）

---

## M14 — 公開レイアウト & Custom Page: 完了（2026-06-12）

| Issue | PR | Summary |
| --- | --- | --- |
| #307 | #308 | feat: 公開ページのレイアウト基盤（型付きプリセット選択・Phase 1） |
| #309 | #310 | feat: 2/3カラム + フィールドの領域配置・並び順（Phase 2A） |
| #312 | #313 | feat: サニタイズ版 html フィールド型（CSS可/JS不可・Phase 2B） |
| #316 | #317 | feat: Custom Page 3A — bundle フィールド型（サンドボックス iframe 隔離）+ custom レイアウト |
| #318 | #319 | feat: Custom Page 3B — iframe 自動高さ(postMessage) + SEOテキスト必須 |
| #322 | #323 | fix: custom レイアウトの meta 必須を公開時のみに限定・失敗理由表示 |
| #320 | #321 | feat: フィールド領域(region)・表示順の管理UX仕上げ |
| #314 | #315 | feat(auth): セッショントークンを httpOnly Cookie へ移行（XSS耐性） |

---

## M13 — 管理コンソール再設計: 完了（2026-06-12）

| Issue | PR | Summary |
| --- | --- | --- |
| — | #298 | feat: 管理コンソールを Console 仕様に再設計・エンティティタイプ並び替え |
| — | #306 | docs(rules): design-export/ をローカル専用・非追跡とするルール追記 |

---

## M12 — メディアライブラリ強化: 完了（2026-06-12）

| Issue | PR | Summary |
| --- | --- | --- |
| #299 | #300 | feat(media): ストレージ抽象化 + Local/S3互換ドライバ（Phase A） |
| #299 | #301 | feat(media): 画像の寸法・alt テキスト・storage_key（Phase B） |
| #299 | #302 | feat(media): オンデマンド画像派生（プリセット + WebP/AVIF + キャッシュ・Phase C） |
| #299 | #303 | feat(media): 公開・管理でレスポンシブ画像（srcset・Phase D） |
| #304 | #305 | feat(media): メディアの使用箇所逆引き + 削除ガード |

---

## M11 — 通知・運用機能の拡充: 完了（2026-05-28）

| Issue | PR | Summary |
| --- | --- | --- |
| #263 | #267 | feat: 通知チャンネルモジュール（email/slack/discord/chatwork/webhook） |
| #273 | #280 | feat: 通知チャンネル管理 GUI |
| #264 | #292 | feat: コメントスパム対策（honeypot + IP/エンティティ別レート制限） |
| #285 | #295 | feat: Webhook を DB キュー＋ワーカー方式で非同期化 |
| #282 | #282 | feat: ユーザープロフィール編集・メールアドレス変更・プロフィール更新 |
| #283 | #294 | feat: メールアドレス変更の確認トークンフロー |
| #265 | #291 | feat: エンティティ一覧の検索クエリを URL(?q=) に反映 |
| #268 | #270 | fix: 組織作成時にデフォルトコンテンツタイプを自動シード |
| #269 | #269 | test(e2e): 完全シナリオ（ブログ・LP・ペルソナ 50 テスト） |
| #286 | #296 | test: feature フック単位の Vitest テスト拡充 |

---

## M10 — マルチテナント包括テストスイート + E2E 拡充: 完了（2026-05-27）

| Issue | PR | Summary |
| --- | --- | --- |
| #256 | #257 | test: マルチテナント包括テストスイート（PHPUnit 単体・1機能・ショート・完全シナリオ） |
| #260 | #259 | test(e2e): nene-corpus パターン参考の包括的ブラウザ E2E テストを追加する |
| #261 | — | docs: E2E 拡充後のドキュメント更新 |

**PHPUnit（545 tests, +171）:**
- `CapabilityMiddleware` org スコープテスト（8）
- `LoginUseCase` org_id 出力テスト（4）
- `ListUsersUseCase` org フィルターテスト（6）
- `CreateUserUseCase` org 割当テスト（6）
- `InviteUser` org 伝播テスト（6）

**Playwright E2E（157 tests, +90）:**
- 09-ui-states: ローディング・disabled・ダブルサブミット防止（6）
- 10-accessibility: ARIA 属性・ラベル・キーボード（11）
- 11-error-recovery: エラー回復フロー（10）
- 12-crud-flows: 複数作成・ナビ・削除確認（8）
- 13-role-access: admin/editor/superadmin 役割マトリクス（20）
- 14-pages: Comments/Navigation/Webhooks/Media/Settings/Dashboard（27）
- 15-repetition: 繰り返し安定性・長期フロー（8）
- 16-auth-lifecycle: Bearer ヘッダー・セッション・期限切れ・401（10）

---

## M9 — マルチテナント完成（1ユーザー=1組織）: 完了（2026-07-01）

| Issue | PR | Summary |
| --- | --- | --- |
| #251 | #252 | feat: マルチテナント基盤を実装する（DB・JWT・CapabilityMiddleware） |

**変更内容:**
- `users` テーブルに `organization_id` / `org_role` 追加（superadmin は NULL）
- `organizations` テーブルに `external_id` 追加（NeNe Corpus 将来連携用）
- `organization_users` テーブルを DROP（1ユーザー=1組織方針・未使用）
- JWT に `org_id` を埋め込み（superadmin = null、admin/editor = 所属 org ID）
- `CapabilityMiddleware` で JWT org_id vs 解決済み org_id を照合（不一致 → 403）
- `ListUsers` / `CreateUser` / `InviteUser` を org スコープ対応

---

## M8 完了（マージ済み）

| Issue | PR | Summary |
| --- | --- | --- |
| #211, #212 | #213 | feat: テナント解決モード設定 UI（single/subdomain/path） |
| #214 | #216 | feat: 既存データ組織割当 UI（シングル→マルチ移行） |
| #215 | #216 | feat: JSON 完全エクスポート／インポート（ID リマッピング付き） |

---

## M7 完了（マージ済み）

| Issue | PR | Summary |
| --- | --- | --- |
| #205, #206 | #210 | feat: organizations テーブル・CRUD API・OrgResolverMiddleware |
| #207 | #210 | feat: 全リポジトリの organization_id スコープ適用 |
| #208 | #210 | test: Organization HTTP テスト（13 テスト） |
| #209 | #210 | feat: スーパー管理画面（組織 CRUD React UI） |

---

## M6 完了（マージ済み）

| Issue | PR | Summary |
| --- | --- | --- |
| #200 | #201 | fix: Relation フィールド公開ページでパーマリンクパターンを使ったリンク生成 |
| #202, #203 | #204 | feat: コメント機能（投稿・一覧・モデレーション API ＋管理 UI ＋公開フォーム） |

---

## M5 完了（マージ済み）

| Issue | PR | Summary |
| --- | --- | --- |
| #190, #191 | #194 | feat: ユーザー管理 API・招待・パスワードリセット |
| #192 | #195 | feat: ユーザー管理フロントエンド（一覧・招待・パスワード変更） |
| #196, #197 | #198 | feat: メディアライブラリ（一覧・削除 API ＋管理画面） |

---

## 次フェーズ候補

| 項目 | 概要 | 難易度 | 優先度 |
| --- | --- | --- | --- |
| 人気記事の精度向上 | per-view ロギング（entity_id 明示記録）で管理 GET を除外し正確化 | 中 | P2 |
| Consumer i18n | 公開 detail / アーカイブ系の多言語化 | 中 | P2 |
| AI Consumer Chat | NeNe Records を外部 API として使うチャットシステム（**別リポジトリ**） | 大 | P2 |
| スケジュール公開改善 | `scheduled_at` ジョブ状態 UI・失敗ハンドリング | 小〜中 | P2 |
| ウィジェット表示順 UI | region 内のウィジェット並び替え（現状 display_order は固定 0） | 小 | P3 |

> 完了済み（旧候補）: コメント通知/スパム対策（#263/#264）、コンテンツ検索（#265 → 公開検索 #335 で実現）。

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
composer check                    # tests + PHPStan + CS-Fixer + OpenAPI + MCP
npm run check --prefix frontend   # type-check + lint + test + storybook
docker compose exec app vendor/bin/phinx migrate
docker compose exec app php tools/seed-blog-demo.php http://localhost
```
