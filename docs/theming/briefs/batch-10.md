# Batch: 公開サイト テーマ 10 種（雰囲気を被らせない）

<!--
ClaudeDesign 向けの一括依頼。EPIC #367 / ハイブリッド #390。
10 テーマを独立した bundle として出力する。狙いは「現行 7 テーマ・10 テーマ相互ともに雰囲気が被らない」こと。
参照: ../theme-authoring.md / ../public-theme-contract.md / ../theme-flags.md /
../class-hooks.md / ../public-theme.schema.json / reference/preview.html（正準マークアップ）
作り込み量と font 上書きのお手本: reference/noir.components.css / reference/newsprint.components.css /
reference/consumer.components.css（最新の納品例）
-->

## 0. 共通の前提（必読）

- **スコープ**: 公開サイトのテーマのみ。CSS は `.nene-public[data-theme='<id>'|'<id>-dark']`（または `^='<id>'`）。**DOM 不変・任意 JS なし**。
- **出力（各テーマ）**: `manifest.json` / `tokens.css`（light+dark 2 ブロック）/ `components.css`（★主役・既存クラスへの上乗せ）/ `screenshots/`（preview-light・preview-dark ＋ **thumbnail.webp 16:7 必須**）。
- **フォント（落とし穴）**: base 既定は display=Saira / body=Inter / mono=JetBrains。**異なるフォントを使うなら `--font-display`/`--font-sans`/`--font-mono` を必ず上書き**（`theme-authoring.md` §4）。許可リスト外フォントは **@fontsource にあるもの**から選び、`fonts` に明記（取り込み側で self-host 同梱する）。
- **AA**: `text-primary`・`on-accent` は背景に 4.5:1 以上を light/dark 双方で。色は OKLCH ＋ `color-mix`。
- **manifest**: `flags` は enum のみ。`description` は **500 字以内**。`version` は 1.0.0。
- **個性を CSS で作る**: tokens だけの再配色で終わらせない。`components.css` で `.hero`/`.featured`/`.card`/`.section__head`/タイポ/メディアを大胆に作り込む。

## 1. 既存 7 テーマ（これらと被らせない）

| id | 雰囲気 | 幅 / 色 / 声 |
| --- | --- | --- |
| consumer (Terracotta) | 暖色フォトマガジン | 標準 / 暖クレイ / 親しみサンス・写真主役 |
| aurora | 寒色テック・設計図 | 広 / 寒ティール / コンデンス＋mono・罫線 |
| reading | 親密なセリフ読み物 | 狭 / 暖クリーム / セリフ・静か |
| noir | ダーク・大胆 | 標準 / 近黒＋ライム / 大型グロテスク |
| botanical | 自然・くつろぎ | 標準 / セージ緑 / 柔らかセリフ・丸 |
| pastel | 多色・遊び | 標準 / コーラル＋ミント / 丸ポップ |
| newsprint | 新聞・多段 | 広 / 赤＋墨 / セリフ・高密度・罫線 |

## 2. 新 10 テーマ — 差別化マトリクス

| # | id | 雰囲気の核 | 明暗 | 主色 | 声/タイポ | 形 |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | swiss | スイス/国際タイポ・モダニズム | 明 | 無彩＋赤 | 巨大グロテスク・厳格グリッド | 角ゼロ |
| 2 | luxe | エディトリアル・ラグジュアリー（ファッション誌） | 暗base | 黒＋金 | 高コントラスト Didone セリフ | 細・余白大 |
| 3 | synthwave | レトロフューチャー・ネオン（80s） | 暗 | 藍＋マゼンタ/シアン | ネオン発光・グリッド地平線 | 角丸中 |
| 4 | japandi | 和×北欧・ゼン極小 | 明 | 温かいストーン/ベージュ | 細い humanist・余白最大 | 角小 |
| 5 | funk | レトロ70s・ファンク | 明 | マスタード/ラスト/アボカド | 丸く太い表示書体・グルーヴ | 角丸大 |
| 6 | academia | ダーク・アカデミア（学術・古典） | 暗 | バーガンディ/フォレスト/羊皮紙 | 古典セリフ・装飾罫 | 角小 |
| 7 | terminal | ターミナル/CRT（ハッカー） | 暗 | 琥珀 or 燐光グリーン on 黒 | 全 mono・走査線 | 角ゼロ |
| 8 | memphis | メンフィス/80s ポップ・グラフィック | 明 | 原色＋黒 | 太字・幾何の紙吹雪 | 角丸大 |
| 9 | coastal | 地中海/コースタル・爽やか | 明 | アズール＋砂＋白 | 風通しの良い humanist | 角丸中・余白大 |
| 10 | nebula | グラデ/グラスモーフィズム・現代 SaaS | 明/暗 | 多色グラデ（紫青桃） | クリーンサンス・磨りガラス | 角丸大 |

> 重複回避メモ: swiss は newsprint と色が近いが**巨大グロテスク＋厳格グリッド**で差別化（newsprint は serif 高密度）。terminal は noir/aurora と暗系だが**全 mono＋CRT**で別物。academia は reading とセリフだが**暗・古典・装飾**で別物。synthwave は noir と暗だが**装飾的レトロ**で別物。memphis は pastel と遊びだが**高彩度原色＋黒輪郭の図形**で別物。coastal は aurora と青系だが**暖かく爽やかな humanist**で別物。

## 3. 各テーマ仕様

> 値は方向性。曖昧な箇所は契約 §3 の派生式で補完。`<id>` はそのまま使う（リネーム禁止）。

### 1. swiss — Swiss / International
- concept: スイス・スタイル（国際タイポグラフィ）。白地に黒の巨大グロテスク、厳格なベースライングリッド、唯一の赤い差し色。機能的でストイック。
- color: accent=シグナルレッド `oklch(56% 0.20 27)` / surface light=純白寄り `oklch(99% 0 0)` / dark=近黒 `oklch(16% 0 0)` / mood=neutral
- type: display=巨大グロテスク（@fontsource: "Archivo" 800 など、無ければ Space Grotesk/Saira）/ body=Inter / mono=JetBrains。**display を上書き**。
- shape: density=comfortable / radius=sharp / width=広め ~1320px
- flags: feedLayout=grid, cardStyle=flat, media=grayscale, hero=minimal, sectionRule=heavy, eyebrow=caps
- 差別化: newsprint（serif・高密度・赤）に対し **巨大グロテスク＋大胆な余白＋厳格グリッド**。

### 2. luxe — Editorial Luxe
- concept: ハイファッション誌。黒（または濃インク）地に金、極端な余白、高コントラストの Didone セリフ、ドラマティックで上品。
- color: accent=ゴールド `oklch(78% 0.13 85)` / surface light=暖オフ白 `oklch(97% 0.006 80)` / dark=黒に近い `oklch(14% 0.005 60)` / mood=warm。**dark を主役寄り**でも可。
- type: display=Didone 高コントラストセリフ（@fontsource: "Playfair Display" 700/900）/ body=Inter or "Source Serif 4" / mono=JetBrains。**display 上書き必須**。
- shape: density=comfortable（余白大）/ radius=sharp / width=標準〜広 ~1240px
- flags: feedLayout=magazine, cardStyle=flat, media=duotone, hero=fullbleed, sectionRule=hairline, eyebrow=caps
- 差別化: reading/newsprint のセリフとは別の**ファッション誌の Didone＋金＋余白の劇場性**。

### 3. synthwave — Retro-future Neon
- concept: 80s レトロフューチャー。藍/紫の暗い地に、マゼンタとシアンのネオン発光、グリッドの地平線、グロー。装飾的でノスタルジック。
- color: accent=ネオンマゼンタ `oklch(70% 0.26 350)` / 第2色=シアン（brand-violet を `oklch(80% 0.14 200)`）/ surface dark=深い藍 `oklch(18% 0.06 280)` / light=淡い藤 `oklch(96% 0.02 300)` / mood=cool
- type: display=レトロな幾何/ワイド（@fontsource: "Orbitron" or "Space Grotesk" 700）/ body=Inter / mono=JetBrains。**display 上書き**。
- shape: density=comfortable / radius=soft / width=標準
- flags: feedLayout=magazine, cardStyle=shadowed（ネオングロー）, media=duotone, hero=fullbleed, sectionRule=heavy, eyebrow=barred
- 差別化: noir（編集的・ライム・ミニマル）に対し **装飾的レトロ・紫ネオン・グロー**。

### 4. japandi — Zen Minimal（和×北欧）
- concept: 和モダン×北欧の極小主義。温かいストーン/ベージュ、最大限の余白、細い humanist、静謐。装飾を削ぎ落とす。
- color: accent=くすんだ土/インディゴ `oklch(50% 0.04 250)`（控えめ）/ surface light=温ベージュ `oklch(95% 0.008 80)` / dark=温チャコール `oklch(22% 0.006 70)` / mood=neutral-warm
- type: display=細い humanist サンス（@fontsource: "Nunito Sans" or Inter light）/ body=Inter / mono=JetBrains
- shape: density=comfortable（余白最大）/ radius=small / width=やや狭 ~1080px・本文 measure 広め
- flags: feedLayout=list, cardStyle=flat, media=plain, hero=minimal, sectionRule=hairline, eyebrow=plain
- 差別化: botanical（緑・有機）に対し **無彩〜ストーンの神経質なほどの極小・余白**。

### 5. funk — Retro 70s Funk
- concept: 70年代ファンク。マスタード/ラスト/アボカドの暖色、丸く太い表示書体、グルーヴィでレトロ、楽しいが落ち着いた暖かさ。
- color: accent=ラスト/パンプキン `oklch(62% 0.15 55)` / 第2色=アボカド `oklch(60% 0.11 120)` / surface light=クリーム `oklch(95% 0.03 85)` / dark=暖ブラウン `oklch(22% 0.03 60)` / mood=warm
- type: display=丸く太いレトロ表示（@fontsource: "Fredoka" or "Bricolage Grotesque" 800）/ body=Inter / mono=JetBrains。**display 上書き**。
- shape: density=comfortable / radius=round / width=標準
- flags: feedLayout=magazine, cardStyle=shadowed, media=duotone, hero=standard, sectionRule=heavy, eyebrow=barred
- 差別化: terracotta（現代の暖色マガジン）に対し **70s パレット＋丸太ゴシックのレトロ・ファンク**。

### 6. academia — Dark Academia
- concept: ダーク・アカデミア。バーガンディ/フォレスト/羊皮紙、古典的セリフ、装飾的な罫、学術的で重厚・ノスタルジック。
- color: accent=バーガンディ `oklch(46% 0.13 25)` / 第2色=フォレスト `oklch(42% 0.08 150)` / surface light=羊皮紙 `oklch(94% 0.02 85)` / dark=深い書斎の暗 `oklch(20% 0.02 40)` / mood=warm。**dark を主役寄りでも可**。
- type: display=古典セリフ（@fontsource: "Source Serif 4" 700 or "EB Garamond"）/ body=Source Serif 4 / mono=JetBrains。**display/sans 上書き**。
- shape: density=comfortable / radius=small / width=標準 ~1180px
- flags: feedLayout=magazine, cardStyle=bordered, media=duotone, hero=standard, sectionRule=heavy, eyebrow=caps
- 差別化: reading（明・クリーム・静か）に対し **暗・古典・装飾・重厚な学術トーン**。

### 7. terminal — Terminal / CRT
- concept: ハッカーのターミナル。近黒に燐光グリーン（または琥珀）、全 mono、走査線/CRT のニュアンス、技術的でニッチ。
- color: accent=燐光グリーン `oklch(82% 0.20 145)`（代替: 琥珀 `oklch(80% 0.15 75)`）/ surface dark=ほぼ黒 `oklch(14% 0.01 150)` / light=淡い灰 `oklch(96% 0.005 150)` / mood=cool。**dark 主役**。
- type: display=mono / body=mono / mono=JetBrains。**display/sans を JetBrains（mono）に上書き**＝全 mono。
- shape: density=compact / radius=sharp / width=標準
- flags: feedLayout=list, cardStyle=bordered, media=grayscale, hero=minimal, sectionRule=hairline, eyebrow=caps
- 差別化: noir/aurora（暗・テック）に対し **全 mono＋CRT 燐光＋走査線**の徹底したターミナル。

### 8. memphis — Memphis / 80s Pop
- concept: メンフィス・デザイン。高彩度の原色（赤・青・黄）＋黒、幾何の図形と斜線、黒い輪郭、ポップでグラフィック、大胆。
- color: accent=原色レッド `oklch(60% 0.22 25)` / 第2色=ブルー `oklch(60% 0.18 250)`・イエロー `oklch(85% 0.17 95)` / surface light=純白 `oklch(99% 0 0)` / dark=近黒 `oklch(17% 0 0)` / mood=neutral
- type: display=太字グロテスク（"Space Grotesk" 700）/ body=Inter / mono=JetBrains
- shape: density=comfortable / radius=round / width=標準
- flags: feedLayout=grid, cardStyle=framed, media=plain, hero=standard, sectionRule=heavy, eyebrow=barred
- 差別化: pastel（淡い・柔らか）に対し **高彩度原色＋黒輪郭＋幾何図形のグラフィック**。

### 9. coastal — Mediterranean / Coastal
- concept: 地中海/コースタル。アズールブルー＋砂＋白、風通しの良い余白、明るく爽やか、humanist でリラックス。
- color: accent=アズール `oklch(58% 0.13 235)` / 第2色=砂/テラ `oklch(78% 0.06 70)` / surface light=白に近い `oklch(98% 0.008 220)` / dark=深い夜の海 `oklch(22% 0.03 240)` / mood=cool-warm
- type: display=humanist サンス（@fontsource: "Nunito" or Inter）/ body=Inter / mono=JetBrains
- shape: density=comfortable（余白大）/ radius=soft / width=広 ~1300px
- flags: feedLayout=magazine, cardStyle=shadowed, media=plain, hero=standard, sectionRule=hairline, eyebrow=plain
- 差別化: aurora（寒色・テック・硬い）に対し **暖かく明るい海辺の humanist・余白**。

### 10. nebula — Gradient Glassmorphism
- concept: 現代 SaaS のグラデ/グラスモーフィズム。紫〜青〜桃の柔らかいグラデ、磨りガラス（半透明＋ぼかしは `backdrop-filter`）、クリーンで未来的。
- color: accent=バイオレット `oklch(60% 0.20 295)` / 第2色=桃/シアンのグラデ / surface light=淡い紫白 `oklch(98% 0.012 300)` / dark=深い藍紫 `oklch(20% 0.04 285)` / mood=cool
- type: display=クリーンな幾何サンス（"Space Grotesk" or Inter）/ body=Inter / mono=JetBrains
- shape: density=comfortable / radius=round / width=標準
- flags: feedLayout=magazine, cardStyle=shadowed, media=plain, hero=fullbleed, sectionRule=none, eyebrow=plain
- 差別化: 既存に無い **グラデ＋磨りガラスの現代 SaaS**。`backdrop-filter` は OK（外部リソース不要）。

---

## 4. 受け入れ条件（各テーマ共通）

- `manifest.json` が `public-theme.schema.json` に合格（必須トークン網羅・`supportsModes:[light,dark]`・`flags` enum・`description` ≤500字）。
- CSS は `.nene-public` スコープ・**DOM 不変・任意 JS なし・外部 `url()`/`@import` 禁止**（data URI/相対のみ）。
- **base 既定と異なるフォントは `--font-*` を必ず上書き**＋ `fonts` に @fontsource 名を明記。
- **thumbnail.webp（16:7）必須**（ピッカーのカードに出る）。
- 検査: `reference/preview.html` で light/dark とフラグを目視 → 取り込み側で `npm run validate:themes`。
