# テーマ オーサリング契約（MD → テーマ bundle）

> ClaudeDesign（や人間の作者）が **Markdown 仕様を書く → テーマ bundle が生成される** ための入力契約。Phase 3（#373）/ エピック #367。
>
> - 値・トークンの正本: [`public-theme-contract.md`](./public-theme-contract.md)
> - manifest スキーマ: [`public-theme.schema.json`](./public-theme.schema.json)
> - 検査: `npm run validate:themes --prefix frontend`（`npm run check` にも組込み済み）

---

## 1. ねらい

「MD を渡せば正確にテーマが組み上がる」状態にする。入力（作者が書く MD）と出力（生成される bundle）の境界を固定し、出力は必ずバリデータを通す。これにより**安全に量産**できる。

```
authoring.md ──(ClaudeDesign / 作者)──▶ theme bundle ──(validate:themes)──▶ 登録
```

## 2. 入力＝オーサリング MD の形式

1 テーマ = 1 つの `*.theme.md`。次の見出しを**この順で**含める。値は具体値でも方向性でもよい（曖昧な指定は §4 の派生ルールで補完）。

```markdown
# Theme: <表示名>

## Meta

- id: <kebab-case> # [data-theme] のベース値
- modes: light, dark # 両対応必須
- concept: <1〜2行のコンセプト（雰囲気・用途）>
- author: <任意>

## Color

- accent: <色の方向性 or oklch 値> # 主要ノブ
- surface (light): <紙の地（明）>
- surface (dark): <紙の地（暗）>
- text: <本文色の方向性（任意・既定は surface から AA 導出）>
- mood: warm | cool | neutral # 影・ボーダーの色温度

## Typography

- display: <見出しフォント> # 例: "Saira Semi Condensed"
- body: <本文フォント>
- mono: <コードフォント>
- baseFontSize: <例 1.0625rem>
- typeScale: <1.0〜1.6 の比率>

## Shape & density

- density: compact | cozy | comfortable
- radius: sharp | soft | round

## Assets (任意)

- hero: <記述 or 同梱ファイル名>
- logo: <同梱ファイル名 / svg>
- decoration: <装飾の方向性>

## References (任意)

- <ムードボード / 参考リンク / 既存サイト 等>
```

> フォントは**許可リスト / セルフホスト**のみ（外部 `@import` 不可）。指定外フォントは同梱が必要。

## 3. 出力＝テーマ bundle

オーサリング MD から次を生成する（[`public-theme-contract.md`](./public-theme-contract.md) §9 準拠）。

```
<theme-id>/
├─ manifest.json     # public-theme.schema.json 準拠（id/name/supportsModes/fonts/tokens/assets…）
├─ tokens.css        # [data-theme='<id>'] と [data-theme='<id>-dark'] の 2 ブロック
├─ components.css    # 任意・既存クラスへの上乗せのみ（新規 DOM 構造なし）
├─ assets/           # hero / logo / decoration …（外部 url 不可・同梱 or data URI）
└─ screenshots/      # preview（light/dark/mobile）
```

## 4. 変換規則（MD → トークン）

- **Knob のみ作者が決める**。**Derived は契約 §3 の派生式で自動生成**する：
  - `accent` → `accent-hover`（light: L−6 / dark: L+7）, `accent-weak`（`color-mix` 12%/16%）, `on-accent`（AA 確保）, `focus-ring`。
  - `surface` → `surface-raised/-overlay/-sunken`（明度オフセット）, `border`/`border-strong`, `text-muted`。
  - `baseFontSize`＋`typeScale` → `--text-*` 一式（clamp の下限/上限も比例）。
  - `density` → `--space-*` 一括スケール。`radius` → `--radius-sm/md/lg`。
  - `mood` → 影・ボーダーの色温度。
- **light/dark 双方**を必ず出力。`color-scheme` も各モードで設定。
- **アクセシビリティ**: `text-primary` / `on-accent` は背景に対し WCAG AA（4.5:1）。

## 5. 受け入れ（必須）

生成 bundle は次を満たすこと。CI（`npm run check`）でも強制される。

- `validateManifest`（schema）に合格。
- `validateThemeCss`（スコープ強制 / `@import` 禁止 / 外部 `url()` 禁止 / 危険構文排除）に合格。
- 必須トークン網羅・light/dark 両セット存在。
- （順次対応）コントラスト AA・SVG サニタイズ。

## 6. 登録

検査を通った bundle を組み込む。

1. `tokens.css` を `frontend/src/shared/ui/theme/themes/<id>.css` として配置し、`frontend/src/pages/consumer/consumer-theme.css` から `@import`（`[data-theme]` スコープなので非アクティブ時は無害）。
2. `frontend/src/shared/lib/public-themes.ts` の `PUBLIC_THEMES` に id / name / preview を追加。
3. `manifest.json`（と任意のスクショ）を `docs/theming/themes/<id>/` に置く。
4. `npm run validate:themes` で緑を確認。

> 第三者テーマ（Tier 2）も同じ契約・同じ検査を通す。任意 JS は不可（宣言的設定のみ）。

## 7. 例

実例のオーサリング MD: [`examples/aurora.theme.md`](./examples/aurora.theme.md)。
