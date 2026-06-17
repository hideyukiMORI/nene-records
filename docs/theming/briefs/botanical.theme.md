# Theme: Botanical

<!--
ClaudeDesign 向けの依頼ブリーフ（記入済み）。EPIC #367 / ハイブリッド #390。
参照: ../theme-authoring.md / ../public-theme-contract.md / ../theme-flags.md /
../class-hooks.md / ../public-theme.schema.json / reference/preview.html（正準マークアップ）
記入例: ../examples/aurora.theme.md / ../examples/ink.theme.md / noir.theme.md
-->

## Meta

- id: botanical
- modes: light, dark
- concept: 自然でくつろいだトーン。セージ/グリーンの暖かみ、広い余白、丸い角、柔らかい影。ライフスタイル/ブログ/カフェのような親しみ。consumer（暖色マガジン）より有機的でゆったり。
- author: NeNe Records

## Color

- accent: セージ〜オリーブの緑。既定 `oklch(56% 0.10 145)`。代替＝深いフォレスト `oklch(46% 0.09 150)` / テラコッタ寄りの差し色も可。
- surface (light): わずかにグリーン/クリームの暖白 `oklch(98% 0.009 120)`
- surface (dark): 暖かいグリーン・チャコール `oklch(22% 0.015 150)`
- text: 暖かいダークグリーン・グレー（彩度低め）
- mood: warm

## Typography

- display: 柔らかいヒューマニスト。既定 "Source Serif 4"（柔らかセリフ）。よりオーガニックにするなら @fontsource の "Fraunces"（ソフトセリフ）追加提案も可（許可リスト外は fonts に明記）。
- body: "Inter"
- mono: "JetBrains Mono"
- baseFontSize: 1.0625rem
- typeScale: 1.18 # ゆるやかな階層

## Shape & density

- density: comfortable
- radius: round # 丸みで柔らかさ

## Flags（構造選択・#390）

- feedLayout: magazine
- cardStyle: shadowed # 柔らかい影で浮かせる
- media: framed # 画像に余白マット＝額装の落ち着き（plain でも可）
- hero: standard
- sectionRule: hairline # 細い罫で軽やかに
- eyebrow: plain # 控えめなラベル

## Assets（任意）

- hero: 既定（任意で葉/有機的な装飾・data URI / SVG）
- logo: 既定（NeneMark 流用）
- decoration: 微かな葉・グレイン・有機的グラデの方向性
- thumbnail（ピッカー用）: 16:7 のスクショ 1 枚（→ `frontend/public/theme-thumbnails/botanical.webp`）

## Approach

- **components.css（L2）で有機的な空気感**を作る：柔らかい影、丸いメディア、ゆったりした余白リズム、見出しの柔らかさ。お手本の作り込み量は `reference/aurora.components.css`。
- 「緑にしただけ」で終わらせない。`--space-*`/`--radius-*` とカード/ヒーローの意匠で“くつろぎ”を出す。

## References

- 既存4テーマ（consumer暖色 / aurora寒色tech / reading serif / noirダーク）に対し、Botanical は**有機的・ゆったり・柔らかい緑**。

---

## 指示（ClaudeDesign 向け・受け入れ条件）

> 正本は `../theme-authoring.md` §3–§5 と `HANDOFF.md`。以下は Botanical 固有の要点。

### 出力する bundle

```
botanical/
├─ manifest.json     # schema 準拠。id=botanical / supportsModes:[light,dark] / fonts / tokens / flags / description / author / version
├─ tokens.css        # [data-theme='botanical'] と [data-theme='botanical-dark'] の 2 ブロック
├─ components.css    # ★主役。既存クラスへの上乗せのみ（新規 DOM なし）
├─ assets/           # 任意。外部 url 不可（同梱 or data URI）
└─ screenshots/      # preview-light / preview-dark（＋できれば mobile）。1 枚は 16:7 のサムネ候補
```

### 守ること

- CSS は **`.nene-public[data-theme='botanical'|'botanical-dark']`**（または `^='botanical'`）にスコープ。
- **DOM は変えない**・**任意 JS なし**・**外部 `url()`/`@import` 禁止**（data URI / バンドル相対のみ）・危険構文禁止。
- **Knob のみ決め、Derived は契約 §3 の派生式で生成**。色は **OKLCH ＋ `color-mix`**。
- **コントラスト AA** を **light/dark 双方**で。
- 検査: `reference/preview.html` で目視 → `npm run validate:themes --prefix frontend`。登録は `../theme-authoring.md` §6。
