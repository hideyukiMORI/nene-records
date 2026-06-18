# ランタイムテーマ — データ駆動テーマ登録（MCP / ClaudeDesign 量産）

> 設計ドキュメント（ドラフト）。テーマを**ビルド時の静的アセット**から**ランタイムのデータ**へ拡張し、ClaudeDesign が **MCP（= OpenAPI 境界）経由でテーマを登録・量産**できるようにする。
>
> - 親: EPIC #367（公開テーマシステム）。前提: [`theme-flags.md`](./theme-flags.md)（ハイブリッドモデル）/ [`public-theme-contract.md`](./public-theme-contract.md)（トークン契約）/ [`public-theme.schema.json`](./public-theme.schema.json)（manifest スキーマ＝**そのまま runtime テーマの形**）。
> - MCP 前提: ツールは **OpenAPI から生成**（`docs/mcp/tools.json` の `source: openapi.yaml`、`composer mcp:generate`）。**OpenAPI にエンドポイントを足せば自動で MCP ツール化**される。

---

## 1. 目的・背景

- **現状の制約**: テーマは 100% ビルド時の静的資産。トークン/コンポーネント CSS は `pages/consumer/consumer-theme.css` に `@import` で固定バンドルされ、カタログは `shared/lib/public-themes.ts` の `PUBLIC_THEMES` にハードコード。**テーマ追加 = ソース編集＋フロント再デプロイ**で、ClaudeDesign が自走で増やせない。
- **ゴール（#367）**: 「テーマ＝JSON（トークンプリセット＋フラグ）、汎用エンジンが 1 回だけ実装」。JSON 語彙のテーマは**コード不要のデータ**なので、保存・配信・適用をランタイム化できれば ClaudeDesign がマス・プロデュースできる。
- **実証済みの足場**: カスタマイザの `overrideCssForTheme`（`theme-customization.ts`）が、保存 JSON から `.nene-public[data-theme='<id>']` にスコープした `<style>`（CSS 変数）を**実行時に**吐いている。これを「上書き差分」から「フルトークン」へ一般化すれば、CSS ファイル無し・再ビルド無しでテーマが成立する。

## 2. 用語

| 種別 | 供給 | 追加方法 | ClaudeDesign |
| --- | --- | --- | --- |
| **built-in テーマ**（現行） | 静的 CSS ＋ `PUBLIC_THEMES` | ソース＋デプロイ | 不可 |
| **runtime テーマ**（本書） | DB の manifest JSON | API（=MCP） | **可（量産）** |
| **bespoke テーマ**（escape hatch） | 手書き `components.css` | ソース＋デプロイ | 不可（語彙外） |

runtime テーマは **JSON 語彙（トークン＋フラグ＋knobs）のみ**。語彙に収まる範囲（`theme-flags.md §1` の「WP 定番テーマ級」）で ClaudeDesign が自由に量産する。

## 3. データモデル

- **`themes` テーブル（org スコープ・マルチテナント）**: `public-theme.schema.json` 準拠の manifest を JSON で保持。
  - 列（案）: `id`(PK), `organization_id`, `theme_key`(`^[a-z][a-z0-9-]{1,40}$`), `name`, `version`, `manifest`(JSON, longtext), `source`(`runtime`固定), `created_by`, `created_at`, `updated_at`。`UNIQUE(organization_id, theme_key)`。
  - built-in テーマとは **id 名前空間を分離**（衝突時は built-in 優先 or バリデータで予約語拒否）。
- **manifest = スキーマそのまま**: `id` / `name` / `version` / `supportsModes`(light+dark 必須) / `tokens.light` / `tokens.dark`（契約トークンのみ・`additionalProperties:false`）/ `fonts` / `knobs` / `flags`。

## 4. API / OpenAPI（→ MCP 自動化）

OpenAPI（`docs/openapi/openapi.yaml`）に CRUD を追加 → `composer mcp:generate` で MCP ツール化。

| operationId | method / path | safety | 用途 |
| --- | --- | --- | --- |
| `listThemes` | GET `/api/v1/themes` | read | 管理：runtime テーマ一覧 |
| `getTheme` | GET `/api/v1/themes/{key}` | read | 取得 |
| `createTheme` | POST `/api/v1/themes` | **write** | **ClaudeDesign が登録** |
| `updateTheme` | PUT `/api/v1/themes/{key}` | write | 改訂 |
| `deleteTheme` | DELETE `/api/v1/themes/{key}` | write | 削除 |
| （公開） | GET `/api/v1/public/themes` | read | 公開サイトが active テーマの manifest を取得 |

- 認可: 既存の `manage_settings`／org `org_id` 強制に準拠。MCP の write 系は ClaudeDesign 用のスコープ付き資格情報で。
- リクエスト/レスポンス schema は manifest（`public-theme.schema.json` を OpenAPI components に取り込み or 同期）。

## 5. 公開サイトでの適用（ランタイム）

1. `PublicShell` が `active_theme` を解決。built-in ならば従来どおり静的 CSS の `[data-theme]`。**runtime テーマなら** `GET /api/v1/public/themes` で manifest を取得。
2. `overrideCssForTheme` を**フルトークン版に一般化**（`buildThemeStylesheet(manifest)`）し、`.nene-public[data-theme='<id>']` / `-dark` に**全契約トークンを CSS 変数として emit**。`themeOverrideCss` と同じ `<style>` 注入経路。
3. `PUBLIC_THEMES` を「built-in（静的）＋ runtime（API）」の**合成カタログ**に。管理ピッカー・`resolvePublicThemeId` は合成済みリストを読む。
4. **FOUC 回避**: 既存の localStorage 先行適用（`storeThemeOverridesRaw` 等）と同様に、active テーマ manifest も先行キャッシュして初回ペイントで適用。

## 6. セキュリティ（最重要）

公開サイトに出す CSS は**信頼できないデータ由来**になるため、**既知の CSS 変数 × 検証済みの値のみ emit。生 CSS は一切通さない**。

- **キー allowlist**: emit するのは契約トークン（`public-theme-contract.md` / スキーマ `tokenSet`）のキーのみ。未知キーは破棄（スキーマ `additionalProperties:false` ＋ サーバ検証）。
- **値サニタイズ（核心）**: 各トークン値を**安全な CSS 値だけ**に制限。許可: `oklch()/rgb()/hsl()/#hex`、`color-mix(in oklch,…)`、`clamp()/min()/max()/calc()`、長さ（`px/rem/em/%/vw/vh`）、既知キーワード。**拒否**: `;` `}` `{` `<` `url(` `expression(` `@import` `/* */` 等の**ブレークアウト/外部参照文字**。→ `--key: <value>;` からの脱出を封じる。
- **フォント**: `fonts.source` は `fontsource`（同梱 allowlist）/ `system` のみ runtime 可。`selfhost`（新規ファイル）は**デプロイ必須＝MCP 不可**。外部 `@import` 禁止。
- **アセット参照**: manifest の `assets.*` は**外部 URL（`http(s):`/`//`）と `data:` を拒否**し、`media_id`（整数）または既知拡張子のバンドル相対パスのみ許可（スキーマ `assetRef` 準拠）。詳細は §8。
- **サーバ側バリデータ（PHP）**: `public-theme.schema.json` をミラーした検証＋上記の値サニタイズを `createTheme/updateTheme` で適用（フロント `validate:themes` は build 時用、ランタイムは API 境界で再検証）。不正は 422。
- **org 分離**: テーマは org スコープ。JWT `org_id` 強制で他テナントのテーマを読めない/書けない。
- **MCP safety**: 登録系は `write`。ClaudeDesign の資格情報を最小権限（テーマ CRUD のみ）に。

## 7. 制約・非対象（正直に）

- **bespoke `components.css` は対象外**: 唯一無二の DOM 装飾は安全にデータ化できない → **デプロイ必須**。MCP で作れるのは JSON 語彙テーマのみ。
- **新フォント（selfhost）はデプロイ必須**。runtime テーマは同梱フォント allowlist 内。
- **語彙の天井**: 真に新規な版面はフラグ/トークンに収まらない（`theme-flags.md §7`）。
- **アセット**: サムネイル等は §8 の方式（自動スワッチ＋ media_id）。bespoke 画像同梱はデプロイ必須。

## 8. サムネイル/プレビュー（#426）

管理ピッカーで runtime テーマを見分けるための見た目。**画像必須にしない**のが要点（MCP はバイナリを送れない）。2方式を組み合わせる。

### B案（既定・推奨）— トークンからの自動スワッチ

- ピッカーは `PublicThemeMeta.preview = { surface, raised, accent }` の**3色スワッチ**を持つ（画像が無いときの既存フォールバック）。
- runtime テーマは **manifest の `tokens.light` から `color-surface` / `color-surface-raised` / `color-accent` を取り出して preview を自動生成**。ClaudeDesign は何も指定しなくても識別可能なカードになる。
- **画像不要＝常に安全・常に成立**。MCP 量産のデフォルトはこれ。

### A案（任意）— 実写サムネを media_id で

- 実スクショを出したいテーマだけ、`manifest.assets.preview` に **`media_id`（整数）**を持つ。
- 画像は**既存 Media API でアップロード**（ClaudeDesign が生成→アップロード）→ id を manifest に格納 → 公開 API がサーバ側で **URL に解決**（`logo_media_id` と同流儀／#299 オンデマンド派生）。
- MCP `createTheme` でバイナリは送らない。「アップロードは Media、テーマは id 参照」で分離。

### 検証（`ThemeManifestValidator` 拡張）

- `assets.preview` 等は **`media_id`（正整数）** か **`assetRef`（外部URL/`data:` 拒否・既知拡張子のバンドル相対パス）** のみ許可。それ以外は 422。
- `data:`/外部URL を弾くことで、ピッカー描画時の SSRF/混入を防ぐ。

### 解決フロー

```
manifest.assets.preview
  ├─ media_id（整数）→ 公開API がメディア URL へ解決 → ピッカー <img>
  └─ 未指定        → tokens.light から 3色スワッチを自動生成（既定）
```

> 既定は B（自動スワッチ）。A（media_id）は opt-in。built-in テーマの `public/theme-thumbnails/<id>.webp` 方式は静的テーマ専用で runtime には使わない。

## 9. ClaudeDesign の量産フロー（想定）

```
ブリーフ（docs/theming/briefs/*.theme.md 相当）
   │  ClaudeDesign がトークン＋フラグを設計
   ▼
POST /api/v1/themes  { manifest JSON }   ← MCP(write)
   │  サーバ検証（スキーマ＋値サニタイズ）→ 保存（org スコープ）
   ▼
管理ピッカーに即出現（合成カタログ）→ 管理者が active_theme で採用
   ▼
公開サイトが manifest を <style> 適用（再ビルド無し）
```

## 10. 実装スライス（提案）

1. ✅ 本ドキュメント＋ Issue（方向確定）。
2. ✅ **backend**: `themes` テーブル（マイグレーション）＋ Repository ＋ CRUD UseCase/Handler ＋ サーバ側バリデータ（スキーマ＋値サニタイズ）＋ org スコープ。（#423 Phase B）
3. ✅ **OpenAPI ＋ MCP**: エンドポイント定義 → `npm run codegen`（フロント型）＋ `composer mcp:generate`（MCP ツール）。（#423 Phase C）
4. ✅ **公開適用（end-to-end）**: `/api/v1/public/themes`（公開・ETag）＋ `buildThemeStylesheet`（トークン→スコープ `<style>`、値ガード）＋ PublicShell で runtime active テーマを適用、assetRef 検証、`swatchFromManifest`（§8 B案）（#423 Phase D）。**FOUC キャッシュ**：runtime テーマの CSS＋flag attrs を localStorage（`nene_public_runtime_theme`）に先行保存し、settings/themes 両クエリ settle 前は初回ペイントへ同期適用（`readStoredRuntimeTheme`/`storeRuntimeTheme`）。
5. ✅ 管理 UI：テーマピッカーに runtime テーマを**合成表示**（built-in＋runtime、`swatchFromManifest` でカード）、active 解決を runtime キー対応、採用は `active_theme` 書込み。runtime カードに**編集（manifest JSON エディタ・サーバ再検証）／削除（確認ダイアログ）**を追加（#423 Phase E）。
6. ⬜ サムネ A案（media_id）opt-in：manifest 解決＋ピッカー `<img>`（#426）。
7. ⬜ ClaudeDesign 連携：MCP 資格情報・ブリーフ→manifest の運用手順。

## 11. 関連

- [`theme-flags.md`](./theme-flags.md)（ハイブリッドモデル・フラグ語彙）/ [`public-theme-contract.md`](./public-theme-contract.md)（トークン契約）/ [`public-theme.schema.json`](./public-theme.schema.json)（manifest スキーマ）。
- [`header-patterns.md`](./header-patterns.md)（ヘッダー文法：runtime テーマでも flags として同居）。
- 既存ランタイム適用: `frontend/src/shared/lib/theme-customization.ts`（`overrideCssForTheme` / `resolveOverrideStyle` の値ホワイトリスト方針）。
