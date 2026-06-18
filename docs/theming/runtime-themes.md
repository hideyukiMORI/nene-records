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
- **サーバ側バリデータ（PHP）**: `public-theme.schema.json` をミラーした検証＋上記の値サニタイズを `createTheme/updateTheme` で適用（フロント `validate:themes` は build 時用、ランタイムは API 境界で再検証）。不正は 422。
- **org 分離**: テーマは org スコープ。JWT `org_id` 強制で他テナントのテーマを読めない/書けない。
- **MCP safety**: 登録系は `write`。ClaudeDesign の資格情報を最小権限（テーマ CRUD のみ）に。

## 7. 制約・非対象（正直に）

- **bespoke `components.css` は対象外**: 唯一無二の DOM 装飾は安全にデータ化できない → **デプロイ必須**。MCP で作れるのは JSON 語彙テーマのみ。
- **新フォント（selfhost）はデプロイ必須**。runtime テーマは同梱フォント allowlist 内。
- **語彙の天井**: 真に新規な版面はフラグ/トークンに収まらない（`theme-flags.md §7`）。
- **アセット**: サムネイル等は別途（media 経由 or 自動生成スワッチ）。

## 8. ClaudeDesign の量産フロー（想定）

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

## 9. 実装スライス（提案）

1. ⬜ 本ドキュメント＋ Issue（方向確定）。← **A: 本スライス**
2. ⬜ **backend**: `themes` テーブル（マイグレーション）＋ Repository ＋ CRUD UseCase/Handler ＋ サーバ側バリデータ（スキーマ＋値サニタイズ）＋ org スコープ。
3. ⬜ **OpenAPI ＋ MCP**: エンドポイント定義 → `npm run codegen`（フロント型）＋ `composer mcp:generate`（MCP ツール）。
4. ⬜ **公開適用（end-to-end）**: `buildThemeStylesheet` 一般化、合成カタログ、`/api/v1/public/themes`、FOUC キャッシュ。
5. ⬜ 管理 UI：runtime テーマの一覧/採用/削除（ピッカー拡張）。
6. ⬜ ClaudeDesign 連携：MCP 資格情報・ブリーフ→manifest の運用手順。

## 10. 関連

- [`theme-flags.md`](./theme-flags.md)（ハイブリッドモデル・フラグ語彙）/ [`public-theme-contract.md`](./public-theme-contract.md)（トークン契約）/ [`public-theme.schema.json`](./public-theme.schema.json)（manifest スキーマ）。
- [`header-patterns.md`](./header-patterns.md)（ヘッダー文法：runtime テーマでも flags として同居）。
- 既存ランタイム適用: `frontend/src/shared/lib/theme-customization.ts`（`overrideCssForTheme` / `resolveOverrideStyle` の値ホワイトリスト方針）。
