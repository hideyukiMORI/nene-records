# Current Work

Last updated: 2026-06-24

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

**M16 — 公開サイトのテーマシステム（Epic #367・コア）: 完了（2026-06-20）**

**ポストM16 — リッチページ／ブロック・型チェック是正・i18n 6言語化: 完了（2026-06-22〜24）**
（#486 型付き投稿ブロック / #489 型チェック実稼働化（tsc 1197→0）/ #390 ハイブリッドテーマ engine /
#530 管理画面6言語化。詳細は下記「ポストM16」セクション。リッチページ #491 / Custom Page 厳格契約 #311 /
フロント全体監査 #507 は一部進行中。）

最新の検証基準（2026-06-24 実測）: PHPUnit 846 tests / 2305 assertions / PHPStan level 8（1156 files）/
CS-Fixer / OpenAPI / MCP（69 tools）＋ Playwright E2E 220 tests（20 files）＋
`npm run check`（type-check / lint / prettier / test 367（75 files）/ validate:themes / knip / storybook）全グリーン。

---

## 進行中エピック（現在のフロンティア）

> 直近の作業文脈は新しいハンドオフが正本:
> `docs/todo/handoff-2026-06-23-rich-pages-blocks.md`（リッチページ／ブロック・最新）/
> `docs/todo/handoff-2026-06-17-public-theme-system.md`（テーマシステム）/
> `docs/todo/handoff-2026-06-19-mcp-connector.md`（MCP コネクタ）。

現在の主戦線は **リッチページ化／Custom Page 厳格契約（#491 / #311）** と
**フロントエンド全体監査の remediation（#507）**、加えて **公開サイトのテーマ作り込み（#362 / #367 系）の派生フェーズ**。
テーマのコア（runtime テーマ・カスタマイザ・スタイルフラグエンジン・MCP bridge）は M16＋#390 で完了済み。

| Issue | 内容 | 状態 |
| --- | --- | --- |
| #491 | リッチページ化 epic（ブロックを本文の主役に／レイアウトブロック／bundle 完全カスタム） | 進行中（WS1–WS3 S3a–S3d 済・残: S3e=ClaudeDesign 連携＋scoped creds＋JSON-LD） |
| #311 | Custom Page（sandboxed iframe + 二重表現SEO + ClaudeDesign 連携）厳格契約 | 進行中（S3a–S3d 済・S3e 残。#491 と一体） |
| #507 | フロントエンド全体監査の remediation（18 WS + セキュリティ + lint 強化） | 進行中（#508–531 マージ済み・残: WS-04/05/14/15/18・ENT-7 等＝要 API/プロダクト判断） |
| #372 | テーマ カスタマイザ（トークン上書きノブ）Phase 2 | 進行中（残: 画像ノブ logo/hero・メディア #299 連携、ライブプレビュー iframe） |
| #371 | モーション/インタラクション能力レイヤ Phase 1.5 | 進行中（基盤＋scroll-reveal #477・header sticky-shrink #479・hero split/gradient #481 済。残: View Transitions 等） |
| #373 | ClaudeDesign「MD→テーマ」量産パイプライン＋バリデータ Phase 3 | 未着手 |
| #362 | 公開サイトのテーマ/見た目の作り込み（design-first / ダークモード対応）epic | 進行中（上記テーマ系を束ねる） |

> 完了化されたフロンティア: **#390**（ハイブリッドテーマ engine・**クローズ**）/ **#402**（feedColumns・**クローズ**）/
> **#435**（ClaudeDesign 向け MCP 実践ガイド・**クローズ**）。

### 完了済みエピック（旧「進行中」）

- **#347 名前付きメニュー移行（location → menus）: 完了** — PR1 #348 / PR2a #349 / region 一本化 #350・#351。
  `navigation_items.location` は migration（`20260614000000_drop_location_from_navigation_items`）で撤去済み・`NavLocations` 参照ゼロ。
  項目は `menu_id` で名前付きメニューに所属、ヘッダー/フッターは region ウィジェットで描画。
  `menus.location`（テーマ表示場所）は方針どおり存置。
- **#352 外観 › レイアウトビルダー: 完了** — スライス1〜5（#353〜#358）＋ページ別 cfg・公開接続（#360/#361/#408）まで全マージ。
  `layout_config` 設定（`20260617000001_add_layout_config_setting`）で永続化済み。

---

## ポストM16 — リッチページ／ブロック・型チェック是正・i18n（2026-06-22〜24）

M16 後に main へマージした作業。正本は `docs/todo/handoff-2026-06-23-rich-pages-blocks.md`。

| Issue | PR | 状態 | Summary |
| --- | --- | --- | --- |
| #486 | #487 / #488 | 完了 | 型付き投稿ブロック EPIC。本文ブロック5種 `text/callout/hero/gallery/chart` ＋ ホーム hero。第一者描画（任意 JS 無し）/ サーバ検証（信頼境界）/ sr-only SEO 投影 / URL allowlist。 |
| #489 | #490 | 完了 | フロント型チェック是正。root tsconfig が src を1ファイルも検査していなかったのを `tsc -p tsconfig.app.json --noEmit` に修正し、隠れていた tsc エラー **1197→0**。以後 CI が型を実検査。 |
| #491 | #492–#499 | 進行中 | リッチページ化 EPIC。WS1=コンテンツタイプ スターター（blank/article/rich_page/custom_page）、WS2=レイアウトブロック `group/columns/spacer/divider`（深さ2・leaf-only）、WS3=dual-SEO bundle（`{html,seoText}`・seoText 必須）/ full-bleed custom レイアウト / `text_fields.value` を LONGTEXT 化。**残: S3e**（ClaudeDesign 連携＋scoped creds＋JSON-LD）。 |
| #311 | #496–#499 | 進行中 | Custom Page 厳格契約（#491 WS3 と一体）。S3a–S3d 済・S3e 残。 |
| #501 | #502–#514 | 完了 | ブロック関連フロントの構造リファクタ（規約整合・重複排除・god 分割・UI 共通化）。 |
| #390 | #394 / #401 | 完了 | テーマを「JSON プリセット＋汎用エンジン（スタイルフラグ）」へ — ハイブリッド。全6フラグ base CSS・カスタマイザ UI・validator 実装済み（EPIC クローズ）。 |
| #507 | #508–#531 | 進行中 | フロントエンド全体監査の remediation（18 WS）。i18n（6言語化 #530/#531 含む）/ フォーム a11y（useId）/ 状態色のセマンティックトークン化 / formatter 集約 / theme query-key factory 化 / vendor 分割（620→209kB）等。残: WS-04/05/14/15/18・ENT-7（要 API/プロダクト/アーキ判断）。 |
| #530 | #531 | 完了 | 管理画面を6言語（en/ja/de/fr/pt-BR/zh-Hans）全980キーで完備。`locales.test` を5ロケール完全性＋プレースホルダ整合へ拡張。 |
| #528 | #529 | 完了 | auth/superadmin 等の bypass ルートで access-log が orgId 未設定により ERROR を出していたのを是正（bypass 分岐で `orgId.set(0)`）。 |

**既知の後始末（handoff §4.2）:** フィールド `region`（sidebar/aside）はレコードで未配線のデッドコード（配線 or 撤去の判断保留）/ bundle の low 項目（divider 厳格検証・公開ページのサーバ CSP backstop 等）/ エディタのライブプレビュー（任意）。

---

## M16 — 公開サイトのテーマシステム（Epic #367・コア）: 完了（2026-06-20）

公開サイト（consumer フロント）の WordPress 風テーマ機能。**色だけ → 版面ごと変わる
＋管理者カスタマイズ＋ClaudeDesign で JSON 量産**まで到達。正本は
`docs/todo/handoff-2026-06-17-public-theme-system.md` ＋ `docs/theming/`。

| 領域 | PR（抜粋） | Summary |
| --- | --- | --- |
| ヘッダー設定 | #420 / #422 | feat: ヘッダー要素トグル・コンテンツ設定 |
| runtime テーマ基盤 | #425 / #428 / #429 | feat: DB manifest 駆動の runtime テーマ（backend / 公開 / FOUC 回避） |
| テーマサムネ | #430 | feat: テーマサムネのメディア指定 |
| ハイブリッドエンジン | #394 / #401 | feat: スタイルフラグエンジン v2 ＋ ビルトインテーマ量産 |
| feedColumns | #403 / #404 | feat: トップフィードの列数フラグ |
| カスタマイザ | #399 / #405 | feat: chrome 設定 ＋ カスタマイズのトースト |
| MCP bridge | #438 | feat: MCP bridge（OpenAPI 境界経由でテーマ操作を Claude.ai へ） |
| オーサリングガイド | #441 / #443 | feat: getThemeAuthoringGuide（サーバ検証由来の manifest 契約） |
| 描画モデル/フォント/CSS | #444 / #446 / #448 | feat: renderModel ＋ availableFonts ＋ getThemeEngineCss |
| ミニプレビュー | #450 / #452 | feat: テーマピッカーの実物ミニレンダリング |
| テーマ保存/詳細 | #454 / #460 / #462 | feat: 「テーマとして保存」/ 詳細モーダル＋明示適用 / 未保存変更ガード |
| reserved keys 同期 | #458 | fix: RESERVED_KEYS を全ビルトイン id に同期 |

**残（派生フェーズ・進行中）:** #372（画像ノブ・ライブプレビュー）/ #371（モーション層・残 View Transitions 等）/
#373（MD→テーマ パイプライン）。#390（ハイブリッド engine）はクローズ済み。詳細は上記「進行中エピック」。

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
