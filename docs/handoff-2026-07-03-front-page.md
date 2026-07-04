# 指示書: 固定ページをトップページに設定する機能（`front_page` 設定）

> **STATUS (2026-07-03 更新)**: PR-1（バックエンド＋公開挙動）= **PR #702 実装済み・レビュー完了**。
> 現状詳細・レビュー指摘・マージ前修正3件＋PR-2 への引き継ぎは
> **`docs/handoff-2026-07-03-front-page-review.md`** が正本。
> 本書は設計の正本として有効だが、§4-4 の「`GET /` ルート登録」は NENE2 既存 `/` ルートに
> 登録順で負けるため **エッジ層方式**（SingleOriginKernel の app 後段で横取り）に変更済み。
> PR-2（管理UI）は本書 §5 ＋ review 文書 §3-5〜3-7 を実装し、`Closes #701` を持つこと。

- 作成: 2026-07-03（Fable が現状調査のうえ作成。実装担当: Opus）
- 位置づけ: `docs/milestones/2026-06-cms-mid-term.md:66` の P4「フロントページ設定」（P2 / M2 目標のまま未着手）を実装する
- 既存 Issue: **なし**（open/closed 全件を「トップページ/フロントページ/front page/homepage」で検索済み）。§9 の下書きで起票から始めること
- 隣接するが重複しない Issue: #651（permalink/ページ階層）・#658（ページ整理UX）・#362（公開トップ index の見た目）・#536（UX/SSR EPIC）

WordPress の「設定 → 表示設定 → ホームページの表示 = 固定ページ」に相当する機能。
NeNe Records では「固定ページ」は特別な組み込み型ではなく、org 作成時にシードされる
entity type（`pages`）にすぎない（`src/Organization/PdoDefaultContentTypeSeeder.php`）。
したがって設計は **「任意の公開レコード1件をトップページに指定できる」** とし、
固定ページはその代表ユースケースとして UI で優遇する（タイプ初期値 = 固定ページ）。

---

## 0. 進め方（この repo の流儀）

1. §9 の下書きで Issue を起票（起票済みなら番号を確認）
2. `main` から `feat/<issue番号>-front-page-setting` を切る（`main` 直コミット禁止）
3. Conventional Commits（type/scope 英語・説明は日本語・`(#issue)` 付き）
4. PR: 目的・変更点・検証・チェックリスト・`Closes #n`
5. 品質ゲート（PR 前に必須）:
   ```bash
   composer check                    # PHPUnit + PHPStan lv8 + CS-Fixer + OpenAPI + MCP
   npm run check --prefix frontend   # type-check + lint + prettier + test + storybook
   ```

規模が大きいので **PR は2分割を推奨**:
- PR-1（バックエンド+公開挙動）: 設定 seed・バリデーション・`/` SSR・301・sitemap・公開 settings 解決
- PR-2（管理UI）: 設定画面・i18n・（余力があれば一覧バッジ）

---

## 1. 要件（ふるまいの契約）

### 設定
- 設定キー **`front_page`**、`data_type = text`、`default_value = ''`、`is_public = 1`。
  値は「トップページに表示するレコード（entities.id）」の数字文字列。空 = 従来どおり。
- org スコープ（既存 settings 基盤に乗るので自動）。

### 公開側（テナントホストのみ。apex は対象外）
1. `front_page` が有効（レコードが存在・`published`・未削除・同一org）のとき:
   - `GET /`（`Accept: text/html`）は **そのレコードをフルページで SSR** する
     （レコード個別ページと同じテンプレート系。SPA ハイドレーションも同様に動く）。
   - `<link rel="canonical">` は **サイトルート**（base path 考慮）。`og:url` も同様。
   - `<title>` はレコードの meta_title → title フィールドの既存優先順位 + サイト名。
   - `og:type` は `website`（record-detail の `article` 固定を上書きできるようにする）。
   - JSON-LD の BreadcrumbList は出さない（または サイト名1段のみ）。本文側パンくずも非表示。
   - 章ナビ・子ページ一覧など record-detail の派生要素は、出ても実害はないが
     canonical/リンク先が正しいことを確認する。
2. そのレコード自身の canonical permalink（typeパターン URL・カスタム permalink・`/view/...` 旧URL）
   へのアクセスは **301 でルートへリダイレクト**（WordPress の redirect_canonical と同じ。重複コンテンツ防止）。
3. `front_page` が空、またはレコードが非公開/削除/不正値のとき:
   - `/` は **従来挙動（SPA シェル → マガジン風トップ）に完全フォールバック**。500 や白画面は不可。
4. `sitemap.xml`: front page 指定中のレコードは個別 permalink で出さず、ルート `/` として出す
   （`/` エントリが現状無いなら追加。重複は不可）。
5. 公開 settings API（`GET /api/v1/public/settings`）では `front_page` を
   **解決済みの canonical permalink パス文字列**として返す（`media` 型が media id → URL に
   解決するのと同じ流儀、`src/Setting/ListPublicSettingsUseCase.php` の L32-47 参照）。
   無効値のときは空文字で返す（SPA 側フォールバック判定を単純にするため）。
   admin 側 `GET /api/v1/settings` は生の ID 文字列のまま。

### SPA 側（クライアントナビゲーション）
- SSR された `/` からのハイドレーションは record-detail と同じ bootstrap JSON 経由。
- SPA 内遷移で `/` に戻ったとき（bootstrap なし）は、公開 settings の解決済みパスを使って
  レコードビューを描画。空ならこれまでの `PublicIndexPage`。

### 管理側
- 設定 UI（§5）: ラジオ「最新の投稿一覧（標準）」/「固定ページを表示」+ レコード選択。
- 保存時バリデーション: 空文字は常に許可。非空なら「数字」「org 内に存在」「未削除」を必須、
  「published であること」も保存時に要求（後から非公開化された場合は公開側ガードで吸収）。

---

## 2. 現状の構造（調査済みマップ。行番号は目安、実装時にずれていても構造は合っている）

### `/` が今どう返っているか
- `public_html/index.php` → `src/Http/SingleOriginKernel.php:37-44`
  （app 本体 → CustomPermalinkResolver → UrlRedirectResolver → SpaShellFallback の順）。
- `/` に PHP ルートは無く、NENE2 既定ルートが JSON を 200 で返す
  （`vendor/hideyukimori/nene2/src/Http/RuntimeApplicationFactory.php:166-173`）。
- `src/Http/SpaShellFallback.php:45-96` の `isHtmlHome` 特例（L54-56）が
  「`/` + `Accept: text/html` なら **200 でも** シェルで差し替える」ため、
  ブラウザには `frontend/dist/index.html` が返る。→ §7-1 の最重要落とし穴。
- SPA 側: `frontend/src/app/router.tsx:52-56` `PublicHome()` が apex なら `LandingPage`、
  テナントなら `PublicIndexPage`。apex 判定は SpaShellFallback が注入する
  `<meta name="nene:apex">`（`frontend/src/shared/lib/apex.ts`）。
- トップの中身: `frontend/src/pages/consumer/PublicIndexPage.tsx` +
  `frontend/src/features/public-home/hooks/use-public-home-page.ts`
  （最新公開13件の横断フィード + タイプ導線。`home_hero` ブロック設定があれば hero 差し替え）。

### レコード個別ページの SSR（再利用する資産）
- ルート: `src/PublicRecord/PublicRecordRouteRegistrar.php`（permalink catch-all、`/view/...` 301、
  sitemap/robots、公開 API）。
- 解決: `RenderPublicPermalinkHandler.php`（カスタム permalink 優先 → typeパターン逆解決）、
  `RenderCustomPermalinkHandler.php`（404 後段の `CustomPermalinkResolver` から呼ばれる）、
  `PublicPermalinkResolver.php`（`canonicalPath()` あり）。
- データ: `GetPublicRecordViewUseCase.php`（displayFields、title、bootstrap、章ナビ、hierarchy、
  canonicalPath）→ `GetPublicRecordViewOutput`。
- HTML: `RenderPublicRecordViewHandler.php` + `templates/public/record-detail.php`
  （canonical/hreflang/OGP/JSON-LD BlogPosting+BreadcrumbList/SPA mount(dev|prod|none)/bootstrap JSON）。
- ⚠ メモリ既知の注意: `GetPublicRecordViewOutput` は public と preview の **両方の UseCase** が
  組み立てる。Output の形を変えるなら preview 側も追随すること。

### settings 基盤（乗るべきレール）
- テーブル: `setting_defs` / `setting_values` / `setting_revisions`（`database/schema/settings.sql`）。
- Repository: `src/Setting/PdoSettingRepository.php`（org スコープ、`applyValue()` が revision + upsert）。
- 更新: `src/Setting/UpdateSettingUseCase.php` — キー固有バリデーションは
  `BLOCKS_DOCUMENT_SETTINGS = ['home_hero']` のようにハードコード分岐するのが既存流儀。
- 公開配信: `src/Setting/ListPublicSettingsUseCase.php` — `media` 型は id→URL に解決して返す（L32-47）。
- seed migration の手本: `database/migrations/20260706000000_add_home_hero_setting.php`
  （per-org 冪等 insert + down() で DELETE）。
- 認可: `/api/v1/settings` は `ADMIN_ONLY_PREFIXES` 済み（GET 含め認証必須）、
  `/api/v1/public/settings` は公開。**新規プレフィックスを作らない限り追加作業なし**。

### 管理画面の設定 UI パターン
- 単一キー設定の最小手本: `frontend/src/features/manage-appearance/hooks/useHomeHeroPage.ts` +
  `ui/HomeHeroView.tsx`（`useSettingList` + `useUpdateSetting`）。
- 設定ページ: `frontend/src/pages/settings/SiteSettingsPage.tsx`
  （AppearanceView + ManageSiteSettingsView + PermalinkSettingsView）。
- 汎用フォーム `frontend/src/features/manage-settings/ui/ManageSiteSettingsView.tsx` は
  設定一覧を **無フィルタで全描画** する → §7-3。
- レコード選択の既存部品: `frontend/src/features/manage-entities/ui/EntityRelationFilterField.tsx`
  （controlled な「1レコード選択」`<Select>`）と
  `features/manage-entity-relations/ui/RelationFieldPanel.tsx`。
  ただし候補取得が `limit: 20` 固定で検索なし → §5 で拡張方針。
- レコード一覧 API: `GET /api/v1/entities` が `entity_type_id` / `status` / `q`（全文検索）/
  `limit` / `sort` を既にサポート（`src/Entity/ListEntitiesHandler.php`）。管理画面からは
  `useEntityList`（`frontend/src/entities/entity/queries.ts:42`）。
- ラベル解決: `frontend/src/shared/lib/get-record-display-label.ts`。

---

## 3. 設計判断（決定事項）

| 論点 | 決定 | 理由 |
| --- | --- | --- |
| 保存形 | 既存 key-value settings に `front_page`（text, ID文字列） | `home_hero`/`active_theme` と同じレール。専用テーブル・新 data_type は過剰 |
| 新 data_type `record` を作るか | **作らない**（text + キー固有バリデーション） | `media` 型追随より変更面が小さい。将来「404ページ指定」等が増えたら型化を再検討 |
| 対象レコード | 任意の entity type の公開レコード（UI 初期値は `pages` タイプ） | 「固定ページ」は単なるシード型。製品思想（柔軟エンティティ）に合わせる |
| 元 URL の扱い | **301 → `/`** | WordPress と同じ。canonical タグだけで済ませず重複 URL を潰す |
| SSR | **v1 から `/` を SSR する** | EPIC #536 で実URL SSR を最優先にした経緯。トップは最重要 SEO ページで、素材（UseCase/テンプレート）は再利用できるため増分が小さい |
| WP の「投稿ページ」（page_for_posts）相当 | **やらない** | タイプ別アーカイブ `/{typeSlug}` が既にその役割。Issue に将来案として記載のみ |
| UI の置き場所 | `SiteSettingsPage` の先頭に「ホームページの表示」セクション | WP ユーザの心理モデルは「設定→表示設定」。外観(Appearance)は見た目、これは表示内容。※taste の範囲なので施主が外観側を望んだら従う |

---

## 4. 実装タスク — バックエンド

### 4-1. seed migration
- `composer migrations:new -- AddFrontPageSetting` で生成（手で作らない）。
- **⚠ バージョン番号必須確認**: この repo は未来日付の migration が既にある
  （例 `20260709000000_seed_analytics_settings_for_existings_orgs`）。生成された版数が
  `ls database/migrations | sort | tail` の最大値より**後ろ**になっているか確認し、
  前に来るならリネームで最大値+1日以降に繰り上げる（メモリ済みの既知 footgun。
  依存より前にソートされると fresh-DB で silent skip する）。
- 内容は `20260706000000_add_home_hero_setting.php` を踏襲:
  per-org 冪等 insert（`setting_defs` に `front_page`, `text`, default `''`, `is_public=1`,
  label 適切に）、`down()` で削除。新規 org 向けの既定セット
  （`PdoDefaultContentTypeSeeder` 近辺 or settings の初期 seed 経路）に載る場所があるなら追随。

### 4-2. 保存時バリデーション
- `src/Setting/UpdateSettingUseCase.php` に `front_page` のキー固有分岐を追加
  （`BLOCKS_DOCUMENT_SETTINGS` と同じ流儀）。
- ルール: `''` は許可 / 非空なら `ctype_digit`、`EntityRepositoryInterface` で org 内・未削除・
  `published` を確認。違反は既存のバリデーションエラー形式で返す。

### 4-3. 公開 settings の解決
- `src/Setting/ListPublicSettingsUseCase.php` に `front_page` の解決分岐:
  ID → レコード取得 → 有効なら `PublicPermalinkResolver::canonicalPath()` の文字列、
  無効なら `''`。`media` の分岐（L32-47）に並べる。

### 4-4. `/` の SSR ルート
- `src/PublicRecord/PublicRecordRouteRegistrar.php` に `GET /` を登録し、
  新設 `RenderPublicHomeHandler`（`RenderPublicRecordViewHandler` を参考）へ:
  1. apex ホストなら何もしない（既存の apex 判定を再利用。SpaShellFallback が
     `nene:apex` メタを注入している判定ロジックの供給元を探して使う）。
  2. `front_page` を読む → 空/無効/非公開なら **フレームワーク既定と同型の応答を返す
     （または敢えて何もせず既定 JSON に落とす）** → SpaShellFallback がシェルを出す。
  3. 有効なら `GetPublicRecordViewUseCase` でデータを組み、`record-detail.php`
     （必要ならテンプレート変数でフロントページ変奏: canonical=ルート、og:type=website、
     BreadcrumbList 抑制、本文パンくず非表示）で 200 text/html を返す。
     `?lang` ロケール・SPA mount(dev/prod/none)・CSP は record-detail と同じ経路に乗せる。
- **⚠ `SpaShellFallback` の `isHtmlHome`（§7-1）を必ず直す**: 現在は `/` の 200 応答を
  無条件にシェルで差し替えるため、SSR した HTML が捨てられる。
  「app 応答が text/html ならそのまま通す」条件を足すのが最小修正。

### 4-5. 301 リダイレクト
- `RenderPublicPermalinkHandler` と `RenderCustomPermalinkHandler` の解決後に
  「解決レコード == front_page」なら base path 込みの `/` へ 301。
  `/view/...` 旧URL は既存の canonical 301 → さらに `/` へ 301 の2段でも許容
  （直接 `/` に飛ばせるなら尚良し）。

### 4-6. sitemap
- `SitemapXmlRenderer`（`RenderSitemapHandler`）で front_page レコードの個別 URL を
  出さない/ルート `/` に置き換える。`/` エントリ自体が現状あるかを確認して重複を避ける。

### 4-7. OpenAPI
- settings は汎用 key-value パスなので**新パス不要**。public settings の値が
  「解決済みパス」になる旨を description に追記する程度
  （`docs/openapi/openapi.yaml` の PublicSettingListResponse 周辺）。
  スキーマ変更したら `composer openapi` と `npm run codegen --prefix frontend`
  （codegen 差分の commit は pre-commit に弾かれたら `--no-verify`）。

---

## 5. 実装タスク — フロントエンド

### 5-1. 設定 UI（新 feature: `features/manage-front-page` 推奨）
- hook: `useHomeHeroPage.ts` を手本に `useSettingList` + `useUpdateSetting` で
  `front_page` を read/write。加えて:
  - `useEntityTypeList` でタイプ選択（初期値: slug `pages` があればそれ）
  - `useEntityList({ entityTypeId, status: 'published', q, limit: 50〜100, sort: 'updated_at' })`
    で候補取得。**既存 relation picker の limit:20 固定を踏襲しない**こと。
    検索テキスト入力（`q`）を付ける（API は対応済み）。
  - ラベルは `getRecordDisplayLabel` + `useTextFieldList`。
- View（UI 仕様）:
  ```
  ホームページの表示
  (•) 最新の投稿一覧（標準）
  ( ) 固定ページを表示
      コンテンツタイプ: [固定ページ ▼]
      ページを検索: [____________]
      ページ:       [（公開済みのみ）▼]
      現在の設定: 「会社案内」 [公開サイトで見る ↗]（サイトルートへのリンク）
  [保存]
  ```
  - ラジオ「最新の投稿一覧」選択で保存 = `''` を保存。
  - 保存中/保存済みのフィードバックは HomeHeroView の既存パターンに合わせる。
  - **警告状態**: 保存済み ID のレコードが取得できない/非公開になっている場合、
    「設定中のページが見つからないため、最新の投稿一覧が表示されています」を表示。
- ボタン等は既存デザイントークン（primary/ghost/subtle/danger）に従う。
- マウント: `frontend/src/pages/settings/SiteSettingsPage.tsx` の先頭セクション。
- i18n: `frontend/src/shared/i18n/messages/` の **6 ロケール全部**
  （ja, en, fr, de, pt-BR, zh-Hans）に `admin.frontPage.*` を追加（`admin.homeHero.*` に倣う）。

### 5-2. 汎用設定フォームとの二重表示回避
- `ManageSiteSettingsView.tsx` は設定を無フィルタ全描画するため、`front_page` が
  生の ID テキスト欄としても出てしまう可能性が高い。`home_hero` / `theme_overrides` が
  どう除外されているかをまず確認し、**同じ機構で `front_page` を除外**（機構が無ければ
  除外リストを導入し既存の専用UI持ちキーもまとめる）。

### 5-3. 公開側の分岐
- `frontend/src/pages/consumer/PublicShell.tsx` の `site` 構築に
  `frontPagePath: settings.front_page ?? ''` を追加し、
  `public-site-context.ts` の `PublicSite` 型にも追加。
- `frontend/src/app/router.tsx` の `PublicHome`（または `PublicIndexPage` 冒頭）で分岐:
  1. SSR bootstrap（`#nene-records-public-record-bootstrap`）が存在すればレコードビューを
     ハイドレート（`PublicRecordDetailPage` が bootstrap をどう消費しているか確認して再利用）。
  2. bootstrap 無しでも `site.frontPagePath` が非空なら、そのパスのレコードビューを描画
     （resolve API `GET /api/v1/public/records/resolve` か、`PublicRecordDetailPage` の
     データ取得フックをパス指定で再利用）。パンくず非表示・canonical はルート。
  3. どちらも無ければ従来の `PublicIndexPage`。
- 取得失敗（404 等）時は白画面にせず `PublicIndexPage` へフォールバック。
- `useSiteDocumentMeta`（PublicShell）がクライアント側で title/description を上書きするので、
  front page 表示時はレコード側メタが勝つことを確認。

### 5-4.（余力があれば・PR-2 の任意項目）管理一覧バッジ
- `features/manage-entities/ui/EntityListPanel.tsx` のタイトル行（StatusBadge の隣）に、
  該当レコードへ「トップページ」バッジを表示（`useSettingList` から front_page を引く）。
  props の増やし方は `ManageEntitiesView.tsx` → `use-manage-entities-page.ts` の既存経路。
- レコード編集画面からの「トップページに設定」クイックアクションは今回スコープ外（Issue に将来案として書く）。

---

## 6. テスト

### バックエンド（tests/ の既存流儀に合わせる）
- `UpdateSettingUseCase`: front_page の受理/拒否（空・非数字・他org・削除済み・下書き）。
- `ListPublicSettings`: 有効 ID → canonical パス、無効 ID → 空文字
  （`ListPublicSettingsMediaTest.php` が手本）。
- `/` SSR の HTTP テスト: 設定あり → 200 text/html にレコード title/canonical=ルート、
  設定なし → 従来応答、非公開化後 → 従来応答（フォールバック）。
- 301 テスト: front_page レコードの permalink GET → 301 Location がルート。
- sitemap テスト: front_page レコードの個別 URL が出ない。

### フロントエンド
- 新 View のテスト。**⚠ vitest に RTL の global cleanup は無い** — 複数 render するファイルは
  `afterEach(cleanup)` を必ず書く（メモリ済み footgun）。
- 新しい shared hook のクエリを使うなら `tests/msw` の `server.ts` にハンドラ追加が必要
  （無いと knip / テストが落ちる）。
- `PublicHome` 分岐テスト（frontPagePath あり/なし/取得失敗フォールバック）。
- **⚠ type-check は本気で strict**: `exactOptionalPropertyTypes` + `noUncheckedIndexedAccess` 有効。
  `?? null` / ガード / `?: T | undefined` で書き、`!`（non-null assertion）は使わない。

---

## 7. 落とし穴（必読）

1. **`SpaShellFallback.isHtmlHome` が `/` の 200 を無条件にシェルで差し替える**
   （`src/Http/SpaShellFallback.php:54-56`）。ここを直さないと SSR した HTML が捨てられる。
   app 応答が `text/html` なら素通しに変えること。修正後も「front_page 未設定 + built dist」
   「未設定 + dist 無し（dev）」の両方で従来挙動が保たれるか確認。
2. **migration の版数スキュー**: repo には今日（2026-07-03）より未来の日付の migration が
   既に存在する。`composer migrations:new` は実日付で採番するため、**依存より前にソートされ
   fresh-DB で silent skip** する。必ず最大版数より後ろへ。実 MySQL
   （localhost:13308 / ポートは 180xx/133xx 帯）で `composer migrations:migrate` を通して確認。
3. **汎用設定フォームの二重表示**（§5-2）。
4. **apex ホスト除外**: apex（無テナント頂点、SaaS ランディング）では front page SSR を
   発動させない。サーバ側 apex 判定は SpaShellFallback が `nene:apex` メタを注入している
   実装から辿れる。
5. **`GetPublicRecordViewOutput` は public と preview の両 UseCase が構築**している。
   Output や `record-detail.php` の変数契約を変えるならプレビュー経路も壊れていないか確認。
6. **公開 settings のキャッシュ**: `GET /api/v1/public/settings` は Cache-Control/ETag 付き。
   設定変更が反映されない場合はここを疑う（既存の invalidate 機構に乗っていれば OK）。
7. **`MediaDerivativeUrl` の regex delimiter は `~`**（`#` にしない）— この付近を触った際の既知地雷。
8. i18n は **6 ロケール全部**。1つでも欠けると check が落ちる。

---

## 8. 受け入れ基準・検証手順

受け入れ基準（Issue にもそのまま使う）:
- [ ] 管理画面「設定」に「ホームページの表示」があり、最新一覧⇔固定ページを切り替えられる
- [ ] 固定ページ指定時、公開 `/` がそのレコードを SSR で返す（`curl -H 'Accept: text/html'` で
      title/canonical を目視確認）
- [ ] 指定レコードの元 permalink は 301 で `/` へ
- [ ] 指定解除・レコード非公開化・削除で `/` が従来トップに安全にフォールバック
- [ ] sitemap.xml に重複エントリが無い
- [ ] apex ホストの挙動が不変
- [ ] `composer check` と `npm run check --prefix frontend` が green

実機検証（ローカルスタック: API=18082 / front dev=18084 / MySQL=13308）:
```bash
curl -fsS http://localhost:18082/health
# 固定ページ設定前後で:
curl -s -H 'Accept: text/html' http://localhost:18082/ | grep -E '<title>|canonical'
curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' http://localhost:18082/<front_pageレコードのpermalink>
curl -s http://localhost:18082/sitemap.xml | grep -c '<loc>'
```
可能なら `/verify` スキル相当のエンドツーエンド確認（管理UIで設定→公開側で見る）まで行う。

---

## 9. Issue 下書き

タイトル: `feat(public): 固定ページをトップページに設定できるようにする`

```markdown
## 背景
WordPress の「設定 > 表示設定 > ホームページの表示 = 固定ページ」に相当する機能が無い。
公開トップ `/` は最新レコード横断フィード固定で、コーポレートサイト用途
（トップ = 会社案内などの固定ページ）に対応できない。
`docs/milestones/2026-06-cms-mid-term.md` の P4「フロントページ設定」(P2/M2) が未着手のまま。

## 目的
管理者が任意の公開レコード（代表ユースケース: 固定ページ）をトップページに指定できるようにする。

## スコープ
- org 設定 `front_page`（text, ID, is_public）を追加（seed migration・保存時バリデーション）
- 公開 `/` を指定レコードで SSR（canonical=ルート、元 permalink は 301）
- 未設定・無効時は従来のマガジン風トップへフォールバック
- sitemap の重複排除
- 管理画面「設定」に「ホームページの表示」セクション（ラジオ + タイプ/レコード選択 + 検索）
- i18n 6 ロケール

## スコープ外（将来）
- WP の「投稿ページ」(page_for_posts) 相当 — タイプ別アーカイブ `/{typeSlug}` が既に担う
- レコード編集画面からの「トップページに設定」クイックアクション
- 管理一覧の「トップページ」バッジ（本 Issue 内で余力があれば）

## 受け入れ基準
（§8 のチェックリストを貼る）
```

---

## 10. スコープ外（やらないこと）

- `parent_id` 型のページツリー（#651 で不採用決定済み。permalink 由来の導出階層が正）
- 新 data_type `record` の導入（text + キー固有バリデーションで足りる）
- LP バンドル配信（別議論: `lp-bundle-delivery-trilemma`）との統合 — front_page は
  「レコードをトップに」であり、LP バンドルの一級市民化とは独立に成立する
