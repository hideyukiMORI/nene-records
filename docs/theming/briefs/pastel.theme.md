# Theme: Pastel Pop

<!--
ClaudeDesign 向けの依頼ブリーフ（記入済み）。EPIC #367 / ハイブリッド #390。
参照: ../theme-authoring.md / ../public-theme-contract.md / ../theme-flags.md /
../class-hooks.md / ../public-theme.schema.json / reference/preview.html（正準マークアップ）
記入例: ../examples/aurora.theme.md / ../examples/ink.theme.md / noir.theme.md
-->

## Meta

- id: pastel
- modes: light, dark
- concept: 多色パステルの遊び心。明るく親しみやすい、大きな角丸、大きめメディア、軽快。ポップ/コミュニティ/コミュニティ誌のトーン。Botanical の落ち着きとも Noir の硬さとも対極の「楽しい」軸。
- author: NeNe Records

## Color

- accent: パステルの主役色。既定＝パステルコーラル/ピンク `oklch(72% 0.13 0)`。**第2色として `color-brand-violet` をパステルミント/ブルー**に振り、chips/badges で多色のポップを出す（例 brand-violet=`oklch(80% 0.10 200)`）。
- surface (light): パステル微かな明白 `oklch(99% 0.012 320)`
- surface (dark): やわらかいダークプラム `oklch(24% 0.03 320)`（遊び心のある暗）
- text: 親しみのあるダーク（彩度ほどほど）
- mood: neutral # 影・ボーダーはニュートラル〜わずか暖

## Typography

- display: 丸み/幾何のポップなサンス。既定 "Space Grotesk"。より柔らかくするなら @fontsource の "Quicksand" / "Baloo 2"（丸ゴシック）追加提案も可（fonts に明記）。
- body: "Inter"
- mono: "JetBrains Mono"
- baseFontSize: 1.0625rem
- typeScale: 1.22

## Shape & density

- density: comfortable
- radius: round # 大きな角丸が要

## Flags（構造選択・#390）

- feedLayout: grid # 均等カードで軽快に
- cardStyle: shadowed # ふわっと浮く
- media: plain # カラフルな画像はそのまま見せる
- hero: standard
- sectionRule: none # 罫を消して軽やかに
- eyebrow: barred # 色のあるバーでポップに

## Assets（任意）

- hero: 既定（任意でパステルのブロブ/グラデ装飾・data URI / SVG）
- logo: 既定（NeneMark 流用）
- decoration: 柔らかいブロブ・グラデ・大きな丸の方向性
- thumbnail（ピッカー用）: 16:7 のスクショ 1 枚（→ `frontend/public/theme-thumbnails/pastel.webp`）

## Approach

- **components.css（L2）で“楽しい版面”**：大きな角丸、大きめメディア、chips/badges の多色、軽快なリズム。お手本の作り込み量は `reference/aurora.components.css`。
- パステルは**コントラスト不足になりやすい**ので、text/on-accent は AA を死守（明るい地に淡色文字を避ける）。

## References

- 既存4テーマに対し、Pastel Pop は**多色・大角丸・遊び**。Botanical（落ち着き緑）とは別物の「明るく楽しい」。

---

## 指示（ClaudeDesign 向け・受け入れ条件）

> 正本は `../theme-authoring.md` §3–§5 と `HANDOFF.md`。以下は Pastel 固有の要点。

### 出力する bundle

```
pastel/
├─ manifest.json     # schema 準拠。id=pastel / supportsModes:[light,dark] / fonts / tokens / flags / description / author / version
├─ tokens.css        # [data-theme='pastel'] と [data-theme='pastel-dark'] の 2 ブロック
├─ components.css    # ★主役。既存クラスへの上乗せのみ（新規 DOM なし）
├─ assets/           # 任意。外部 url 不可（同梱 or data URI）
└─ screenshots/      # preview-light / preview-dark（＋できれば mobile）。1 枚は 16:7 のサムネ候補
```

### 守ること

- CSS は **`.nene-public[data-theme='pastel'|'pastel-dark']`**（または `^='pastel'`）にスコープ。
- **DOM は変えない**・**任意 JS なし**・**外部 `url()`/`@import` 禁止**（data URI / バンドル相対のみ）・危険構文禁止。
- **Knob のみ決め、Derived は契約 §3 の派生式で生成**。色は **OKLCH ＋ `color-mix`**。
- **コントラスト AA** を **light/dark 双方**で（パステルは特に注意）。
- 検査: `reference/preview.html` で目視 → `npm run validate:themes --prefix frontend`。登録は `../theme-authoring.md` §6。
