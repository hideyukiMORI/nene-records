# Theme: Terracotta（リデザイン）

<!--
既存テーマ Terracotta（id=consumer）のリデザイン依頼。EPIC #367 / ハイブリッド #390。
現状の問題: bespoke components.css が無く「ほぼ base の既定の見た目」＝ Aurora/Reading と特徴が近く見える。サムネも無い。
参照: ../theme-authoring.md / ../public-theme-contract.md / ../theme-flags.md /
../class-hooks.md / ../public-theme.schema.json / reference/preview.html（正準マークアップ）
お手本（作り込み量・最新の良作）: reference/noir.components.css / reference/botanical.components.css
-->

## 差別化（必達・3テーマ共通の指針）

Terracotta / Aurora / Reading は**初期作で互いに似て見える**。リデザインでは下表の軸を**はっきり振り切る**こと。Terracotta は「**標準軸＝暖色フォトマガジン**」を担う。

| 軸 | **Terracotta** | Aurora | Reading |
| --- | --- | --- | --- |
| 幅 | 標準 ~1180px | 広い ~1320px | 狭い ~1008px |
| 色温度 | **暖色クレイ/オレンジ** | 寒色ティール | 暖色クリーム/ブラウン |
| 声 | **親しみのあるサンス・写真主役のマガジン** | mono/コンデンス・テック・罫線 | セリフ・親密・読み物 |
| メディア | **写真主役・大きく豊か** | duotone/framed・索引的 | 最小・ヘアライン |

## Meta

- id: consumer # ★既存 id を維持（リネーム禁止。アクティブテーマのキー）
- name: Terracotta
- modes: light, dark
- concept: 暖かいフォト・マガジン。テラコッタ/クレイの暖色、写真を主役に大きく扱い、柔らかい角と親しみのある編集レイアウト。NeNe Records の「標準」だが、**base の素のままではなく**、写真と暖色で“雑誌の表紙”のような顔を持たせる。
- author: NeNe Records

## Color

- accent: 暖かいテラコッタ/クレイ。既定 `oklch(58% 0.14 45)`（現行踏襲・微調整可）。
- surface (light): 暖かいオフホワイト `oklch(97.5% 0.012 75)`
- surface (dark): 暖かいダークブラウン・チャコール（クレイ寄り）
- text: 暖かいダーク（彩度低め）
- mood: warm

## Typography

- display: 親しみのある編集サンス。既定 "Saira Semi Condensed"（現行）。よりマガジン的にするなら humanist な選択も可（@fontsource、許可リスト外は fonts に明記）。**base 既定と異なるフォントにするなら `--font-display` を必ず上書き**（authoring §4）。
- body: "Inter"
- mono: "JetBrains Mono"
- baseFontSize: 1.0625rem
- typeScale: 1.18

## Shape & density

- density: comfortable
- radius: soft # 柔らかい角（Aurora の sharp と対比）

## Flags（#390）

- feedLayout: magazine
- cardStyle: shadowed # 柔らかく浮く写真カード
- media: plain # 写真は鮮やかなまま主役に
- hero: standard
- sectionRule: hairline
- eyebrow: plain

## Assets（必達）

- **thumbnail（ピッカー用・必須）**: 16:7 のスクショ → `frontend/public/theme-thumbnails/consumer.webp`。今回の主目的の一つ。
- hero: 既定（任意で暖色グラデ/グレインの装飾・data URI/SVG）
- logo: 既定（NeneMark 流用）

## Approach

- **今回の肝＝bespoke `components.css` を新規に作り込む**こと。現状は tokens のみで“素”なので、写真主役・暖色・柔らか角のマガジン版面を `.card`/`.featured`/`.hero`/`.section__head`/メディアで作る。お手本は `reference/botanical.components.css`（柔らか系）/ `reference/noir.components.css`（作り込み量）。
- version は 2.0.0 に上げる想定。

---

## 指示（ClaudeDesign 向け・受け入れ条件）

> 正本は `../theme-authoring.md` §3–§5 と `HANDOFF.md`。

### 出力 bundle

```
consumer/
├─ manifest.json   # schema 準拠。id=consumer / version=2.0.0 / supportsModes:[light,dark] / fonts / tokens / flags / description / author。description は 500 字以内。
├─ tokens.css      # [data-theme='consumer'] と [data-theme='consumer-dark'] の 2 ブロック
├─ components.css  # ★今回の主役（新規作り込み）。既存クラスへの上乗せのみ（新規 DOM なし）
├─ assets/         # 任意。外部 url 不可（同梱 or data URI）
└─ screenshots/    # preview-light / preview-dark ＋ **thumbnail.webp（16:7・必須）**
```

### 守ること

- CSS は **`.nene-public[data-theme='consumer'|'consumer-dark']`**（または `^='consumer'`）にスコープ。**DOM 不変・任意 JS なし・外部 `url()`/`@import` 禁止**（data URI/相対のみ）。
- **base 既定と異なるフォントは `--font-display`/`--font-sans`/`--font-mono` を必ず上書き**。許可リスト外は `public-fonts.ts` 同梱前提。
- **コントラスト AA** を light/dark 双方で。色は OKLCH ＋ `color-mix`。
- 検査: `reference/preview.html` で目視 → `npm run validate:themes --prefix frontend`。
