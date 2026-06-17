# Theme: Newsprint

<!--
ClaudeDesign 向けの依頼ブリーフ（記入済み）。EPIC #367 / ハイブリッド #390。
参照: ../theme-authoring.md / ../public-theme-contract.md / ../theme-flags.md /
../class-hooks.md / ../public-theme.schema.json / reference/preview.html（正準マークアップ）
記入例: ../examples/aurora.theme.md / ../examples/ink.theme.md / noir.theme.md
-->

## Meta

- id: newsprint
- modes: light, dark
- concept: 新聞・多段のジャーナリズム。インクオンペーパー、serif 見出し、細い罫線だらけ、高密度の多段グリッド、デートライン風メタ。Reading（親密な単行本・狭幅）とは逆の、**広く・密度高く・索引的**な紙面。
- author: NeNe Records

## Color

- accent: インクの差し色。既定＝ニュースプリント・レッド `oklch(50% 0.17 25)`。代替＝クラシックなネイビー `oklch(42% 0.10 250)`。
- surface (light): 暖かいニュースプリントのクリームグレー `oklch(95% 0.008 85)`
- surface (dark): 深いインク・チャコール `oklch(18% 0.006 60)`
- text: 近黒のインク（高コントラスト・彩度低）
- mood: neutral # わずかに暖

## Typography

- display: エディトリアルな serif 見出し。既定 "Source Serif 4"（太め運用）。よりニュース紙的にするなら @fontsource の "Fraunces" / コンデンス serif の追加提案も可（fonts に明記）。Reading と差を付けるため**太く・詰めて**使う。
- body: "Inter" # 本文は可読サンス（または Source Serif でも可）
- mono: "JetBrains Mono" # デートライン/索引のメタに
- baseFontSize: 1rem # 高密度
- typeScale: 1.25 # 見出しの段差を強める

## Shape & density

- density: compact # 紙面密度
- radius: sharp # 角は立てる（紙）

## Flags（構造選択・#390）

- feedLayout: magazine # フィーチャー＋密グリッド
- cardStyle: bordered # 罫で仕切る（flat でも可）
- media: grayscale # 新聞らしいモノクロ写真（hover で発色）
- hero: minimal # アート省略・タイポ主体の一面トップ
- sectionRule: hairline # 細い罫を多用
- eyebrow: caps # mono の小型キャップス（セクションラベル）

## Assets（任意）

- hero: 既定（装飾なし・タイポ主体）
- logo: 既定（NeneMark 流用）
- decoration: 罫・ルール・段組のリズムで魅せる（画像装飾は最小）
- thumbnail（ピッカー用）: 16:7 のスクショ 1 枚（→ `frontend/public/theme-thumbnails/newsprint.webp`）

## Approach

- **components.css（L2）で“多段の紙面”**：`.cardgrid`/`.types` の列を詰める、`.section__head`/カードに細い罫を多用、`.featured` を一面トップ風に、メタを mono のデートライン化、見出しを serif で太く。お手本は `reference/aurora.components.css`（作り込み量）と `reference/reading.components.css`（幅・本文の扱い）。
- Reading との差別化が肝：**狭く親密な Reading に対し、広く密度高い索引的紙面**にする。

## References

- 既存4テーマに対し、Newsprint は**新聞・多段・索引的**。serif だが Reading とは真逆（広幅・高密度・罫線過多）。

---

## 指示（ClaudeDesign 向け・受け入れ条件）

> 正本は `../theme-authoring.md` §3–§5 と `HANDOFF.md`。以下は Newsprint 固有の要点。

### 出力する bundle

```
newsprint/
├─ manifest.json     # schema 準拠。id=newsprint / supportsModes:[light,dark] / fonts / tokens / flags / description / author / version
├─ tokens.css        # [data-theme='newsprint'] と [data-theme='newsprint-dark'] の 2 ブロック
├─ components.css    # ★主役。既存クラスへの上乗せのみ（新規 DOM なし）
├─ assets/           # 任意。外部 url 不可（同梱 or data URI）
└─ screenshots/      # preview-light / preview-dark（＋できれば mobile）。1 枚は 16:7 のサムネ候補
```

### 守ること

- CSS は **`.nene-public[data-theme='newsprint'|'newsprint-dark']`**（または `^='newsprint'`）にスコープ。
- **DOM は変えない**・**任意 JS なし**・**外部 `url()`/`@import` 禁止**（data URI / バンドル相対のみ）・危険構文禁止。
- **Knob のみ決め、Derived は契約 §3 の派生式で生成**。色は **OKLCH ＋ `color-mix`**。
- **コントラスト AA** を **light/dark 双方**で。
- 検査: `reference/preview.html` で目視 → `npm run validate:themes --prefix frontend`。登録は `../theme-authoring.md` §6。
