# ClaudeDesign × ランタイムテーマ — 運用手順

> ClaudeDesign が **MCP 経由で runtime テーマを登録・改訂・量産**するための運用ガイド（#431 / #423）。
> 設計の正本は [`runtime-themes.md`](./runtime-themes.md)。manifest 契約は [`public-theme.schema.json`](./public-theme.schema.json)、トークンは [`public-theme-contract.md`](./public-theme-contract.md)、フラグは [`theme-flags.md`](./theme-flags.md)。

---

## 1. どちらの経路を使うか

| | **runtime テーマ**（本書） | **built-in テーマ** |
| --- | --- | --- |
| 供給 | DB の manifest JSON（API/MCP） | 静的 CSS バンドル＋ `PUBLIC_THEMES` |
| 追加方法 | `createTheme`（**デプロイ不要・即反映**） | MD ブリーフ → bundle → **デプロイ** |
| 語彙 | JSON（トークン＋フラグ＋knobs＋assets） | ＋ bespoke `components.css`（唯一無二DOM） |
| ClaudeDesign | **量産（既定）** | 語彙外の特注のみ |

**原則: 量産は runtime（本書）。** 語彙（トークン/フラグ）に収まらない唯一無二デザイン（独自DOM・新フォント同梱）だけ built-in（[`theme-authoring.md`](./theme-authoring.md)）でデプロイする。

## 2. 認証・資格情報

- `/api/v1/themes`（create/update/delete/list/get）は **ADMIN_ONLY**＝**認証必須**・**org スコープ**（JWT `org_id` 強制）。公開読取 `/api/v1/public/themes` のみ無認証。
- ClaudeDesign は対象 org の**認証済み資格情報**（JWT / API-key、NENE2 ポリシー準拠＝[`../integrations/ai-tools.md`](../integrations/ai-tools.md)）で MCP を駆動する。
- **最小権限**（テーマ CRUD のみを想定）。**本番資格情報は commit・agent context・ログに出さない**。
- 書込み系 MCP ツール（`createTheme`/`updateTheme`/`deleteTheme`）は `safety: write`/`destructive`。実行は人手承認のあるフローで。

## 3. ワークフロー（ブリーフ → 登録 → 反映）

```
1. ブリーフ            docs/theming/briefs/<name>.theme.md（Meta/Color/Typography/Shape…）
        │  ClaudeDesign が契約トークン(light/dark)＋フラグ＋(任意)assets へ落とす
        ▼
2. manifest 生成        §5 の最小形に準拠した JSON
        │
        ▼
3. createTheme (MCP)    POST /api/v1/themes  body=manifest        ← 新規
   updateTheme (MCP)    PUT  /api/v1/themes/{key} body=manifest    ← 改訂（key 不変）
        │  サーバ検証（ThemeManifestValidator）：不正は 422
        ▼
4. 管理ピッカーに出現   外観→テーマ。スワッチ/サムネ付きカード（合成カタログ）
        ▼
5. 採用                管理者が選択 → active_theme 書込み → 公開サイトへ即反映（再ビルド無し）
```

- **新規 vs 改訂**: 既存 key への再登録は `updateTheme`（`createTheme` は key 重複で 422）。`updateTheme` は **manifest.id == URL の key** 必須（rename は delete+create）。
- **バージョン**: 改訂ごとに `version` を semver で上げる（運用上の追跡用）。
- **冪等性**: 同一 manifest の再 PUT は無害。
- **削除**: `deleteTheme`。active_theme に採用中の key を消す前に別テーマへ切替える（公開側は built-in 既定へフォールバック）。
- **コミット前プレビュー（推奨）**: `createTheme`/`updateTheme` の前に **`previewTheme`（計算プレビュー・非永続）** を回す。`POST /api/v1/themes/preview` に manifest を渡すと、実際に効くトークン（`applied`）／落ちた値（`dropped`）／**WCAG コントラスト**（text/surface・on-accent/accent… の AA/AAA 判定）を返す。**AA 未達・dropped・warnings があれば自己修正→再 preview**、OK で初めて commit。ブラウザ無し。視覚スクショは Phase 2（将来）。設計: [`theme-preview.md`](./theme-preview.md)。

## 4. 検証で弾かれるもの（§6 安全規則）

`createTheme`/`updateTheme` はサーバで再検証する。以下は **422**:

- **id**: `^[a-z][a-z0-9-]{1,40}$` 以外、または**予約 id**（built-in テーマ名と衝突）。
- **必須欠落**: `name`/`version`(semver)/`supportsModes`(light+dark)/`tokens.light`/`tokens.dark`、必須契約トークン欠落。
- **トークン値**: `;{}<>` ・ `url(` ・ `@import` ・ `expression(` ・ `javascript:` ・ コメント等の**ブレークアウト/外部参照**（公開 `<style>` への CSS インジェクション防止）。長さ上限 200。
- **フラグ**: 未知キー / enum 外の値。
- **フォント**: `fonts[].source` が `selfhost`（新規ファイルはデプロイ必須＝runtime 不可）。`fontsource`/`system` のみ。
- **アセット**: 外部 URL（`http(s):`/`//`）/ `data:` / 不正パス。`media_id`（正整数）か既知拡張子のバンドル相対のみ。

> ClaudeDesign 側で 422 の `errors[]`（field/message）を読んで自動修正→再 PUT する。

## 5. 最小 manifest（実例）

`tokens.light`/`dark` は[必須契約トークン](./public-theme-contract.md)を全て埋める（下例は抜粋）。

```jsonc
{
  "id": "midnight",
  "name": "Midnight",
  "version": "1.0.0",
  "supportsModes": ["light", "dark"],
  "tokens": {
    "light": {
      "color-surface": "oklch(98% 0.01 250)",
      "color-surface-raised": "oklch(99% 0.008 250)",
      "color-text-primary": "oklch(22% 0.02 250)",
      "color-accent": "oklch(55% 0.16 255)",
      "color-on-accent": "oklch(99% 0 0)",
      "color-scheme": "light"
      /* …必須トークンを全て… */
    },
    "dark": {
      "color-surface": "oklch(18% 0.02 250)",
      "color-scheme": "dark"
      /* …必須トークンを全て… */
    }
  },
  "flags": { "feedLayout": "magazine", "headerLayout": "classic" },
  "assets": { "preview": 42 }   // 任意：Media にアップロード済み画像の media_id（A案）
}
```

**MCP 呼び出し**（`createTheme`）: 入力は上記 manifest そのもの（id/name/version/supportsModes/tokens が必須、その他は additional）。レスポンス `ThemeResponse` に `thumbnail_url`（解決済み）が付く。

## 6. サムネ（カード見た目）

- **B案（既定・推奨）**: 何も指定しない。ピッカーが `tokens.light` の surface/raised/accent から**3色スワッチ**を自動生成。**MCP 量産はこれで十分**。
- **A案（任意・実写）**: 画像を**先に Media API でアップロード** → 得た `media_id` を `assets.preview` に入れる。公開/管理 API が URL 解決し `<img>` 表示。MCP `createTheme` でバイナリは送らない。

## 7. 運用チェックリスト

- [ ] id は新規・非予約（既存 key は `updateTheme`）。
- [ ] light/dark 両方の必須トークンを充足。値は安全な CSS（色は `oklch`/`#hex`/`color-mix` 等）。
- [ ] フラグは enum 内（[`theme-flags.md`](./theme-flags.md)）。ヘッダーは [`header-patterns.md`](./header-patterns.md)。
- [ ] フォントは `fontsource`/`system` のみ。
- [ ] サムネは未指定（B）か `media_id`（A）。外部URL/`data:` は不可。
- [ ] 422 は `errors[]` を見て修正・再送。
- [ ] 採用中テーマの削除前に切替。
- [ ] 本番資格情報を出力・commit しない。

## 8. 関連

- 設計: [`runtime-themes.md`](./runtime-themes.md)（§5 公開適用 / §6 セキュリティ / §8 サムネ）
- 契約: [`public-theme.schema.json`](./public-theme.schema.json) / [`public-theme-contract.md`](./public-theme-contract.md) / [`theme-flags.md`](./theme-flags.md) / [`header-patterns.md`](./header-patterns.md)
- ブリーフ実例: [`briefs/`](./briefs/) / 記入例: [`examples/`](./examples/)
- AI 運用ポリシー: [`../integrations/ai-tools.md`](../integrations/ai-tools.md)
