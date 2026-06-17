# Theme: Reading（リデザイン）

<!--
既存テーマ Reading（id=reading）のリデザイン依頼。EPIC #367 / ハイブリッド #390。
現状の問題: Terracotta/Aurora と特徴が近く見える＆サムネが無い。親密なセリフ読み物を振り切る。
注意: Newsprint（広く密度高い serif 多段）とも明確に別物にする＝Reading は「狭く・静かで・親密」。
参照: ../theme-authoring.md / ../public-theme-contract.md / ../theme-flags.md /
../class-hooks.md / ../public-theme.schema.json / reference/preview.html
お手本: reference/reading.components.css（現行）/ reference/botanical.components.css
-->

## 差別化（必達）

Reading は「**狭幅・暖色クリーム・静かなセリフの読み物**」を担う。下表の軸を振り切る。

| 軸 | Terracotta | Aurora | **Reading** |
| --- | --- | --- | --- |
| 幅 | 標準 ~1180px | 広い ~1320px | **狭い ~1008px・本文 measure 狭** |
| 色温度 | 暖色クレイ | 寒色ティール | **暖色クリーム/ブラウン** |
| 声 | 親しみサンス | mono テック | **セリフ・親密・文学的** |
| メディア | 写真主役 | duotone 索引 | **最小・ヘアライン** |

> Newsprint（赤・広幅・多段・高密度）との差別化が肝：Reading は**狭く・静かで・余白の多い単行本**。

## Meta

- id: reading # ★既存 id 維持
- name: Reading
- modes: light, dark
- concept: 親密な“単行本”。暖かいクリーム紙に静かなブラウンのインク、狭い段組と広い余白、セリフ主体の見出し（Source Serif 4）、文学的なドロップキャップ、ヘアライン罫と小型キャップス。Aurora（広/罫/mono）の対極、Newsprint（広/密/赤）よりも静か。
- author: NeNe Records

## Color

- accent: 落ち着いたブラウン/テラ `oklch(46% 0.07 58)`（現行踏襲・微調整可）
- surface (light): 暖かいクリーム紙 `oklch(97% 0.02 85)`
- surface (dark): 暖かいダークブラウン（読書灯のような）
- text: 静かなブラウン・近黒
- mood: warm

## Typography

- display: "Source Serif 4"（セリフ見出し）。**body もセリフ**にして“本”の声に。`--font-display` と `--font-sans` を Source Serif に上書き（現行どおり・authoring §4）。Saira は小型キャップスのラベル用に温存可。
- body: "Source Serif 4"
- mono: "JetBrains Mono"
- baseFontSize: 1.125rem # 読み物なので大きめ
- typeScale: 1.2

## Shape & density

- density: comfortable # 余白で呼吸
- radius: soft

## Flags（#390）

- feedLayout: list（または magazine）# 落ち着いた縦の流れ
- cardStyle: flat # 罫・影を抑える
- media: plain（最小・控えめ）
- hero: minimal # アート省略・タイポ主体
- sectionRule: hairline
- eyebrow: caps # 小型キャップスのラベル

## Assets（必達）

- **thumbnail（必須）**: 16:7 → `frontend/public/theme-thumbnails/reading.webp`
- hero: 既定（装飾なし・タイポ主体）
- logo: 既定

## Approach

- 現行 `reference/reading.components.css` を**出発点に、狭幅・余白・文学性をさらに強化**。`--content-w` と本文 `--measure` を狭く、`--gutter` を広く、ドロップキャップ、ヘアライン罫、小型キャップス、静かなセリフ見出し。
- version は 3.0.0 に上げる想定。

---

## 指示（ClaudeDesign 向け・受け入れ条件）

> 正本は `../theme-authoring.md` §3–§5 と `HANDOFF.md`。

### 出力 bundle

```
reading/
├─ manifest.json   # schema 準拠。id=reading / version=3.0.0 / supportsModes:[light,dark] / fonts / tokens / flags / description（500字以内）/ author
├─ tokens.css      # [data-theme='reading'] と [data-theme='reading-dark'] の 2 ブロック
├─ components.css  # ★主役。既存クラスへの上乗せのみ（新規 DOM なし）
├─ assets/         # 任意。外部 url 不可
└─ screenshots/    # preview-light / preview-dark ＋ **thumbnail.webp（16:7・必須）**
```

### 守ること

- CSS は **`.nene-public[data-theme='reading'|'reading-dark']`**（または `^='reading'`）にスコープ。**DOM 不変・任意 JS なし・外部 `url()`/`@import` 禁止**。
- **フォント上書き必須**（`--font-display`/`--font-sans` を Source Serif に）。許可リスト外は `public-fonts.ts` 同梱前提。
- **コントラスト AA** を light/dark 双方で。色は OKLCH ＋ `color-mix`。
- 検査: `reference/preview.html` 目視 → `npm run validate:themes --prefix frontend`。
