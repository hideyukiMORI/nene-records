# 引継ぎ — 公開サイト テーマ機能（EPIC #367）

最終更新: 2026-06-17 / ブランチ: main は最新（下記 PR 群すべてマージ済み）

公開サイト（consumer フロント）の「WordPress 風テーマ機能」を作り込んできた長期作業の引継ぎ。**色だけ → 版面ごとガラッと変わる＋管理者カスタマイズ＋JSON量産**まで到達済み。

---

## 1. 何ができている（ユーザー体験）

- 管理画面 **外観 > テーマ** で、公開サイトのテーマを **Terracotta / Aurora / Reading** から選択。切替は**即時反映**（FOUC なし）。
- 同タブの **カスタマイズ** で、アクティブテーマを微調整：アクセント色／背景・文字色（light/dark 別）／本文フォント／幅／余白／角丸／文字サイズ×見出しスケール／密度／**フィード版面（grid/list）／カード意匠**。
- 訪問者は従来どおり light/dark/auto トグル可。各テーマは light/dark 両対応。
- 3 テーマは ClaudeDesign 製で、**幅まで含めて表情が異なる**（Aurora=広い構造テック / Reading=狭い親密な読み物 / Terracotta=標準マガジン）。

---

## 2. アーキテクチャ（どこに何があるか）

- **スコープ**: 公開サイトのみ。すべて `.nene-public` / `[data-theme='<id>'|'<id>-dark']`。管理 UI トークンには非干渉。
- **テーマ = トークン値 ＋ 既存クラスへの上乗せ CSS（components.css）＋ アセット**。`public-site.css`（base/エンジン）は無改修が原則。**DOM 構造は変えない**。
- **配信**: サーバ生成/静的 CSS＋注入 `<style>`。テーマは任意 JS を持たない（JWT が localStorage にあるためインライン JS 不可）。

### 主要ファイル

| 役割                                                 | 場所                                                                                                         |
| ---------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| テーマ tokens（ビルトイン）                          | `frontend/src/shared/ui/theme/themes/{consumer-brand,aurora,reading}.css` ＋ `*.components.css`              |
| すべて `@import` する束ね                            | `frontend/src/pages/consumer/consumer-theme.css`                                                             |
| テーマレジストリ（id/name/preview）                  | `frontend/src/shared/lib/public-themes.ts`                                                                   |
| 訪問者 light/dark 制御                               | `frontend/src/pages/consumer/use-consumer-theme.ts`                                                          |
| カスタマイザ解決（tokens/colors/flags→CSS・data-\*） | `frontend/src/shared/lib/theme-customization.ts`                                                             |
| base/コンポーネント CSS（＋フラグエンジン）          | `frontend/src/pages/consumer/public-site.css`                                                                |
| シェル（テーマ/上書き/フラグ適用）                   | `frontend/src/pages/consumer/{PublicShell,PublicSiteShell}.tsx` ＋ `public-site-context.ts`                  |
| 管理 UI（テーマ選択＋カスタマイズ）                  | `frontend/src/features/manage-appearance/`（`AppearanceLayoutPage` の theme タブ）                           |
| バリデータ（lib＋CLI）                               | `frontend/src/shared/lib/theme-bundle/` ＋ `frontend/scripts/validate-themes.ts`（`npm run check` に組込み） |
| 公開フォント（serif 同梱）                           | `frontend/src/pages/consumer/public-fonts.ts`（@fontsource）                                                 |

### 設定（バックエンド）

- `active_theme`（選択テーマ id）/ `theme_overrides`（カスタマイズの**テーマ別 JSON**）。いずれも `setting_defs` の公開設定。
- **重要な既知の落とし穴（修正済み）**: 設定は当初 `organization_id=0` にしか無く、実 org（`.env` `ORG_SLUG=local` → org 1）で空＝404 だった。`SeedDefaultSettingsForExistingOrgs` / `SeedThemeOverridesSetting` で各 org にシードして解決。**新規組織作成フローの設定シードは未点検（#380 フォローアップ）**。
- 公開設定 API は `Cache-Control: max-age=0, must-revalidate`＋ETag に変更済み（切替の即時反映）。navigation/widgets/menus/record はまだ 5分キャッシュ。

### 契約ドキュメント（`docs/theming/`）

- `public-theme-contract.md`（トークン＋アセット契約・正本）/ `public-theme.schema.json`（manifest スキーマ・`flags` 含む）
- `theme-authoring.md`（MD→bundle オーサリング）/ `class-hooks.md`（components.css が狙えるクラス）/ `theme-flags.md`（**ハイブリッド：スタイルフラグ＆プリセット**）
- `examples/`（記入例 brief）/ `themes/{aurora,reading}/`（納品 bundle）

---

## 3. ハイブリッド方針（#390・進行中）

テーマ定義を「**列挙パラメータ＋スタイルフラグの JSON**」に寄せ、**汎用エンジン（base CSS）が1回だけ実装**。語彙外は bespoke `components.css`（Pro escape hatch）。

- **スタイルフラグ**: `feedLayout` / `cardStyle` / `media` / `hero` / `sectionRule` / `eyebrow`。`.nene-public` の `data-*` で適用（DOM 不変）。語彙・実装方針は `theme-flags.md`、enum は `public-theme.schema.json` と `FLAG_DEFS`（theme-customization.ts）。
- **実装済み**: 全6フラグの base CSS（`feedLayout=list` ＋ `cardStyle` ＋ `media`/`hero`/`sectionRule`/`eyebrow`）＋カスタマイザに全フラグのプルダウン＋validator の flags enum 検証（schema 駆動）。
- **未実装**: カスタマイザ simple/pro 分割（UI 折りたたみ）、ClaudeCode による JSON テーマ量産。

---

## 4. ClaudeDesign / ClaudeCode への出し方（量産フロー）

1. ハンドオフ一式 `design-export/nene-theme-handoff.zip`（**未追跡**・再生成可）を渡す。中身＝契約3点＋`theme-flags.md`＋`class-hooks.md`＋brief テンプレ／記入例＋reference（既存テーマ CSS＋`public-site.css` 全量）。
2. ブリーフ（`brief-*.theme.md`）を埋めて渡す → bundle（manifest＋tokens.css＋任意 components.css＋screenshots）が返る。
3. 手元で **`npm run validate:themes --prefix frontend`**（CI ゲートと同じ）→ 取り込み（tokens.css→`src/.../themes/<id>.css`、`consumer-theme.css` に `@import`、`public-themes.ts` に登録、bundle を `docs/theming/themes/<id>/`）。
4. **これからは JSON `flags` だけのテーマも可**（CSS 不要）。

---

## 5. 残タスク / 次の一手

- **#390 続き（推奨）**: simple/pro 分割（UI 折りたたみ）→ ClaudeCode で JSON テーマ量産。フラグ拡充（media/hero/sectionRule/eyebrow base CSS）・カスタマイザ全フラグ UI・validator flags enum は実装済み。
- **#372 残り**: 画像ノブ（logo/hero、メディアライブラリ #299 連携）、ライブプレビュー（iframe）。
- **#371**: モーション/インタラクション能力レイヤ（hero バリアント / scroll-reveal / View Transitions）。
- **バリデータ強化**: コントラスト AA 自動判定、SVG 深サニタイズ。
- **運用**: 残キャッシュ（navigation/widgets/menus/record）の即時反映化、新規組織の設定シード点検（#380）。

## 6. 開発メモ

- 検証: `npm run check --prefix frontend`（type/lint/prettier/test/**validate:themes**/knip/storybook）、`composer check`（PHPUnit/PHPStan8/CS-Fixer）。
- マイグレーション: `composer migrations:new -- Name`（手動採番禁止）。
- インライン JS 不可（CSS／React style／注入 `<style>` のみ）。第三者テーマは Tier 制（任意 JS は受けない）。
