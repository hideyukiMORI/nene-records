# テーマ計算プレビュー（MCP `previewTheme`）— 設計

> ClaudeDesign が manifest を**結果を見ずに**書いている（盲目オーサリング）状態を、**ブラウザ不要の計算プレビュー**で閉じる（#433 / 親 #423）。コミット前にコントラスト不足・破綻・落ちる値を自己修正できる。
> 関連: [`runtime-themes.md`](./runtime-themes.md) / [`claudedesign-runtime-themes.md`](./claudedesign-runtime-themes.md) / [`public-theme.schema.json`](./public-theme.schema.json)。

---

## 1. なぜ計算プレビューか（Phase 1）

- 盲目オーサリングの**8割の失敗は「読めない配色・コントラスト不足・ダーク反転ミス・値が落ちる」**。これらは**ピクセルを見なくても数値で判定できる**。
- ブラウザ（headless）は重いインフラ。まず純関数で安く閉ループを作り、視覚確認（Phase 2/スクショ）の投資判断材料にする。

## 2. 重要な前提：入力はフルトークン manifest

built-in テーマの base トークンは**フロントの CSS ファイル**であり、サーバ（PHP）にとって**データではない**。よって「built-in＋override の最終色」はブラウザ（`getComputedStyle`）無しに解決できない → **Phase 2 送り**。

本 Phase は **フルトークンを持つ manifest を入力**にする。runtime テーマは常にフルトークンを持つので、これが正しいスコープ。既存 runtime テーマの調整は `getTheme` → tokens を編集 → `previewTheme`（編集後 manifest）→ `updateTheme` の流れ。

## 3. エンドポイント / MCP

| | |
| --- | --- |
| HTTP | `POST /api/v1/themes/preview`（ADMIN_ONLY・**非永続**） |
| MCP | `previewTheme`（`safety: read`／非破壊） |
| 入力 | `ThemePreviewRequest` = manifest（`createTheme` と同形） |
| 出力 | `ThemePreviewResponse`（下記） |

非永続＝DB に触れない（保存は `createTheme`/`updateTheme`）。

## 4. レスポンス

```jsonc
{
  "valid": false,                      // 構造検証の合否（preview は極力 200 で報告）
  "errors": [{ "field": "tokens.dark.color-accent", "message": "…", "code": "unsafe" }],
  "applied": {                         // 描画相当の安全フィルタ後に実際に効くトークン
    "light": { "color-surface": "oklch(98% 0.01 250)", "...": "…" },
    "dark":  { "...": "…" }
  },
  "dropped": [                         // フィルタで落ちたキー（理由つき）
    { "mode": "light", "token": "color-accent", "reason": "unsafe-value" }
  ],
  "contrast": {                        // WCAG コントラスト（§5）
    "light": [
      { "pair": "text-primary/surface", "ratio": 8.9, "aa": true, "aaa": true, "computable": true }
    ],
    "dark":  [ "…" ]
  },
  "warnings": ["tokens.light.color-info uses color-mix(): contrast not computed"]
}
```

## 5. コントラスト判定（WCAG）

各モード（light/dark）で次のペアを計算:

| ペア | 用途 | 基準 |
| --- | --- | --- |
| `text-primary / surface` | 本文可読性 | AA 4.5 / AAA 7.0 |
| `text-muted / surface` | 補助文字 | AA 4.5 |
| `text-primary / surface-raised` | カード上の本文 | AA 4.5 |
| `on-accent / accent` | ボタン文字 | AA 4.5 |
| `accent / surface` | アクセントの視認 | 3.0（UI 部品） |
| `border-strong / surface` | 罫線の視認 | 3.0 |

- 比率 = (L_light + 0.05) / (L_dark + 0.05)。`aa`/`aaa` は基準充足の真偽。
- **色解析**: `#hex` と `oklch(L C H)` を sRGB 相対輝度へ変換して計算。`color-mix()`/`var()`/gradient 等は `computable:false`＋warning（契約色トークンは大半 oklch/hex なので実用上カバー）。
- oklch→sRGB は oklab 経由（標準係数）→ 線形 sRGB → 相対輝度。

## 6. セキュリティ

- 入力トークン値は**既存サニタイズ（`ThemeManifestValidator::isSafeCssValue`）を再利用**。`applied` は実描画と同じ「安全な値のみ」、落ちた値は `dropped` で明示。
- 非永続・org 認証・生CSS不可は runtime と同じ契約。

## 7. ClaudeDesign の使い方

```
getTheme(key) → manifest 取得
  └─ tokens/flags を調整
       └─ previewTheme(manifest) → contrast/dropped/warnings を読む
            ├─ AA 未達や dropped があれば自己修正 → 再 previewTheme
            └─ OK なら updateTheme(key, manifest)   ※新規は createTheme
```

## 8. Phase 2（別 Issue・本書スコープ外）

- headless スクショ：正準マークアップ＋候補 `<style>` を Node/Playwright で描画→PNG（light/dark・PC/SP）。マルチモーダルで“見る”。
- built-in＋override の視覚確認もここ（`getComputedStyle` で base 解決）。

## 9. 実装スライス

1. ✅ 本ドキュメント＋ Issue（#433）。
2. ✅ `ColorContrast`（oklch/hex→相対輝度→比率）＋単体テスト。
3. ✅ `PreviewThemeUseCase`/`PreviewThemeHandler`（validate を捕捉して報告・applied/dropped・contrast）＋ route `POST /api/v1/themes/preview` ＋ DI。
4. ✅ OpenAPI（`ThemePreviewResponse`）＋ `composer mcp:generate`（`previewTheme`・safety read）＋ codegen。
5. ✅ 運用 doc（`claudedesign-runtime-themes.md` §7）に preview ループを追記。
