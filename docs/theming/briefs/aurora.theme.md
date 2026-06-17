# Theme: Aurora（リデザイン）

<!--
既存テーマ Aurora（id=aurora）のリデザイン依頼。EPIC #367 / ハイブリッド #390。
現状の問題: Terracotta/Reading と特徴が近く見える＆サムネが無い。寒色テックの個性を振り切る。
参照: ../theme-authoring.md / ../public-theme-contract.md / ../theme-flags.md /
../class-hooks.md / ../public-theme.schema.json / reference/preview.html
お手本: reference/aurora.components.css（現行）/ reference/noir.components.css（最新の作り込み量）
-->

## 差別化（必達）

Aurora は「**寒色・テック・罫線/索引の広い版面**」を担う。下表の軸を振り切る。

| 軸 | Terracotta | **Aurora** | Reading |
| --- | --- | --- | --- |
| 幅 | 標準 ~1180px | **広い ~1320px** | 狭い ~1008px |
| 色温度 | 暖色クレイ | **寒色ティール/シアン** | 暖色クリーム |
| 声 | 親しみサンス・写真 | **mono/コンデンス・テック・ハード罫線** | セリフ・読み物 |
| メディア | 写真主役 | **duotone/framed・索引的** | 最小 |

## Meta

- id: aurora # ★既存 id 維持
- name: Aurora
- modes: light, dark
- concept: 寒色の構造テック。ティール/シアンの差し色、ハードなヘアライン、採番されたグリッド、mono の技術的レジスター。**幅広**で情報密度の高い“設計図/索引”のような版面。Terracotta（暖色・柔らか）と Noir（ダーク）の両方と明確に別物。
- author: NeNe Records

## Color

- accent: ティール/シアン `oklch(62% 0.13 200)`（現行踏襲・微調整可）
- surface (light): 寒色のオフホワイト `oklch(98% 0.006 230)`
- surface (dark): 寒色の近黒 `oklch(17% 0.02 250)`
- text: 寒色ダーク
- mood: cool

## Typography

- display: "Saira Semi Condensed"（コンデンス・テック）。**base 既定の display も Saira なので、Aurora で別フォントにするなら `--font-display` を上書き**（authoring §4）。
- body: "Inter"
- mono: "JetBrains Mono"（mono を多用＝技術的レジスター）
- baseFontSize: 1.0625rem
- typeScale: 1.18

## Shape & density

- density: comfortable
- radius: sharp # 角ゼロ寄り（Terracotta soft と対比）

## Flags（#390）

- feedLayout: magazine
- cardStyle: framed # アクセントの上端＝索引タイル
- media: duotone # ティール着色のデュオトーン（または grayscale→hover 発色）
- hero: standard（または fullbleed）
- sectionRule: heavy # 太い罫
- eyebrow: caps # mono の小型キャップス

## Assets（必達）

- **thumbnail（必須）**: 16:7 → `frontend/public/theme-thumbnails/aurora.webp`
- hero: 既定（任意で blueprint グリッド装飾・data URI/SVG）
- logo: 既定

## Approach

- 現行 `reference/aurora.components.css` を**出発点に、幅・罫線・索引性をさらに強化**。`--content-w` を広く、`.cardgrid`/`.types` を密な索引グリッドに、`.section__head` をハード罫に、メタを mono のリファレンス番号に。
- version は 3.0.0 に上げる想定。

---

## 指示（ClaudeDesign 向け・受け入れ条件）

> 正本は `../theme-authoring.md` §3–§5 と `HANDOFF.md`。

### 出力 bundle

```
aurora/
├─ manifest.json   # schema 準拠。id=aurora / version=3.0.0 / supportsModes:[light,dark] / fonts / tokens / flags / description（500字以内）/ author
├─ tokens.css      # [data-theme='aurora'] と [data-theme='aurora-dark'] の 2 ブロック
├─ components.css  # ★主役。既存クラスへの上乗せのみ（新規 DOM なし）
├─ assets/         # 任意。外部 url 不可
└─ screenshots/    # preview-light / preview-dark ＋ **thumbnail.webp（16:7・必須）**
```

### 守ること

- CSS は **`.nene-public[data-theme='aurora'|'aurora-dark']`**（または `^='aurora'`）にスコープ。**DOM 不変・任意 JS なし・外部 `url()`/`@import` 禁止**。
- **base 既定と異なるフォントは `--font-*` を必ず上書き**。許可リスト外は `public-fonts.ts` 同梱前提。
- **コントラスト AA** を light/dark 双方で。色は OKLCH ＋ `color-mix`。
- 検査: `reference/preview.html` 目視 → `npm run validate:themes --prefix frontend`。
