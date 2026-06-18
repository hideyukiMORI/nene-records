# ClaudeDesign 向け MCP 実践ガイド — テーマ操作

> **対象読者: ClaudeDesign（テーマを作る AI エージェント）。** ランタイムテーマを MCP ツールで作る・直す・確認する手順書。
> ポリシー/設計は [`claudedesign-runtime-themes.md`](./claudedesign-runtime-themes.md)（運用）・[`runtime-themes.md`](./runtime-themes.md)（設計）・[`theme-preview.md`](./theme-preview.md)（プレビュー）。ツール定義の正本は [`../mcp/tools.json`](../mcp/tools.json)。

---

## 0. 30秒まとめ

1. `getTheme` で既存 manifest を取る（新規なら自分で組む）。
2. token/flag を編集する。
3. **`previewTheme` でコントラスト/落ちる値を確認 → 直す（ループ）。**
4. OK になったら `createTheme`（新規）/ `updateTheme`（改訂）で確定。
5. 公開反映は管理者が `active_theme` を選んだ時（再ビルド不要）。

**鉄則: `previewTheme` を通さずに commit しない。**

## 1. 前提・認証

- 入口は **MCP ツールのみ**（＝OpenAPI HTTP 境界）。DB 直アクセスや CSS ファイル編集はしない。
- テーマ系は対象 org の**認証済み資格情報**が要る（`createTheme`/`updateTheme`/`deleteTheme` は書込み）。**本番資格情報を出力・log・commit しない**。
- 作れるのは **JSON 語彙のテーマ**（トークン＋フラグ＋assets）。手書きCSS・新フォント同梱・独自DOM は範囲外（→ built-in デプロイ。理由は [`runtime-themes.md §7`](./runtime-themes.md)）。

## 2. 使えるツール一覧

| ツール | safety | 用途 | 必須入力 |
| --- | --- | --- | --- |
| `listThemes` | read | runtime テーマ一覧 | — |
| `getTheme` | read | 1件取得（manifest 込み） | `key` |
| `previewTheme` | read | **計算プレビュー（非永続）** | manifest（`id,name,version,supportsModes,tokens`） |
| `createTheme` | write | 新規登録 | manifest |
| `updateTheme` | write | 改訂（manifest 置換） | `key` ＋ manifest |
| `deleteTheme` | destructive | 削除 | `key` |

> `key` = テーマの id（`manifest.id`）。`previewTheme`/`createTheme` の入力は**manifest そのもの**（フィールドを直接トップレベルに渡す）。`updateTheme` は `key`（パス）＋ manifest。

## 3. manifest の作り方

最小で必要なのは `id` / `name` / `version` / `supportsModes` / `tokens`（`flags`/`assets` は任意）。

### 3.1 必須契約トークン（light/dark 両方に全て）

```
color-surface  color-surface-raised  color-surface-overlay  color-surface-sunken
color-text-primary  color-text-muted  color-text-inverse
color-border  color-border-strong  color-focus-ring
color-accent  color-accent-hover  color-accent-weak  color-on-accent
color-brand-violet  color-danger  color-danger-hover
color-ok  color-warn  color-info
shadow-sm  shadow-md  shadow-lg  color-scheme
```

- 値は**安全な CSS のみ**：色は `oklch(...)` 推奨 / `#hex` / `color-mix(in oklch, …)`。`color-scheme` は `light`/`dark`。`shadow-*` は影定義文字列。
- **禁止文字/構文**：`;` `{` `}` `<` `>` `url(` `@import` `expression(` `javascript:` `/* */`、200字超。→ 入ると**その値だけ落ちる**（`previewTheme.dropped` で分かる）。
- 詳細: [`public-theme-contract.md`](./public-theme-contract.md)。

### 3.2 flags（任意・構造プリセット）

`feedLayout`(grid/list/magazine) / `cardStyle` / `hero` / `headerLayout`(nav-right/classic/centered/minimal) / `headerSearch` / … enum は [`theme-flags.md`](./theme-flags.md) ＋ [`header-patterns.md`](./header-patterns.md)。enum 外は 422。

### 3.3 assets（任意・サムネ A案）

`assets.preview` に **media_id（正整数）**。画像は先に Media でアップロードして id を得る。未指定なら tokens から自動スワッチ（B案・推奨）。外部URL/`data:` は不可。

## 4. 黄金ループ（previewTheme → commit）

```
previewTheme(manifest)
  → 返り値を読む:
     valid:false / errors[]      → 構造を直す
     dropped[]                   → その token 値が落ちた（安全文字へ）
     contrast.light/dark[]       → aa:false の pair を直す（色を離す）
     warnings[]                  → color-mix 等は計算不可。心配なら #hex/oklch に
  → 直して再 previewTheme … 全 aa:true & dropped 空になったら
createTheme(manifest)            // 新規
updateTheme(key, manifest)       // 改訂（key==manifest.id）
```

`previewTheme` のレスポンス例（要点）:

```jsonc
{
  "valid": true,
  "errors": [],
  "applied": { "light": { "color-surface": "oklch(98% 0.01 250)", "...": "…" }, "dark": { "...": "…" } },
  "dropped": [ { "mode": "light", "token": "color-accent", "reason": "unsafe-value" } ],
  "contrast": {
    "light": [
      { "pair": "text-primary/surface", "ratio": 12.1, "aa": true, "aaa": true, "computable": true },
      { "pair": "on-accent/accent",     "ratio": 2.9,  "aa": false, "aaa": false, "computable": true }
    ],
    "dark": [ "…" ]
  },
  "warnings": []
}
```

**判定の目安**：本文系ペア（text/surface, text/raised, on-accent/accent）は **AA=4.5 以上**、UI系（accent/surface, border-strong/surface）は **3.0 以上**。`aa:false` は配色を離して再計算。

## 5. レシピ

### 5.1 新規テーマを作る
1. コンセプト → light/dark の全必須トークンを `oklch` で設計（accent と surface/text を十分離す）。
2. `previewTheme(manifest)` → `aa:false`/`dropped` を潰す。
3. `createTheme(manifest)`（id は新規・非予約）。

### 5.2 既存テーマの色を微調整する（あなたの主用途）
1. `getTheme(key)` → `manifest` を取得。
2. 直したい token（例 `color-accent`）を変更。
3. `previewTheme(変更後 manifest)` → on-accent/accent などの AA を確認。
4. OK → `updateTheme(key, manifest)`（`manifest.id == key`、`version` を上げる）。

### 5.3 「ボタン文字が読めない」を直す
- `on-accent/accent` が `aa:false` → `color-on-accent` を accent と反対の明度に（明るい accent には暗い on-accent、暗い accent には明るい on-accent）。再 preview。

### 5.4 サムネ（実写）を付ける
1. 画像を Media でアップロード → `media_id` 取得。
2. `manifest.assets.preview = <media_id>` を入れて `update/createTheme`。レスポンス `thumbnail_url` に解決URL。

## 6. やってはいけない / よくある 422

- 既存 key を `createTheme`（→ `updateTheme` を使う）。`updateTheme` で `manifest.id != key`（rename は delete+create）。
- 予約 id（built-in 名）を使う。
- 必須トークンの欠落、`version` 非semver、`supportsModes` に dark 欠落。
- token 値に禁止文字、フラグ enum 外、`fonts.source: selfhost`、assets に外部URL/`data:`。
- 採用中テーマをいきなり `deleteTheme`（先に別テーマへ）。

> 422 は `errors[]`（field/message/code）を読んで**自動修正→再送**。

## 7. 関連

- 運用ポリシー: [`claudedesign-runtime-themes.md`](./claudedesign-runtime-themes.md)
- 設計: [`runtime-themes.md`](./runtime-themes.md) / プレビュー: [`theme-preview.md`](./theme-preview.md)
- 契約: [`public-theme.schema.json`](./public-theme.schema.json) / [`public-theme-contract.md`](./public-theme-contract.md) / [`theme-flags.md`](./theme-flags.md) / [`header-patterns.md`](./header-patterns.md)
- ツール定義: [`../mcp/tools.json`](../mcp/tools.json)
