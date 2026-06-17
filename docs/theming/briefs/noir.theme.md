# Theme: Noir

<!--
ClaudeDesign 向けの依頼ブリーフ（記入済み）。EPIC #367 / ハイブリッド #390。
- 入力 MD の形式・出力 bundle 構成・変換規則・受け入れ条件: ../theme-authoring.md
- 全トークンの正本: ../public-theme-contract.md
- フラグ語彙（data-* で効く構造選択）: ../theme-flags.md
- 狙えるクラスの全カタログ: ../class-hooks.md
- manifest スキーマ: ../public-theme.schema.json
- 正準マークアップ（このHTMLにCSSを当ててレンダリング確認）: handoff の reference/preview.html
記入例: ../examples/aurora.theme.md（寒色・round）/ ../examples/ink.theme.md（無彩色・sharp）
-->

## Meta

- id: noir
- modes: light, dark # 両対応必須（dark が主役、light は反転した明るい変種）
- concept: ダーク主体・大胆な高コントラストのエディトリアル。漆黒〜近黒の地に、エレクトリックな差し色と大きな display タイポ。夜・音楽・カルチャー誌のトーン。light モードは反転した明るい姉妹版（AA は両モードで確保）。
- author: NeNe Records

## Color

- accent: エレクトリック。**既定＝アシッドライム** `oklch(86% 0.21 128)`（暗い地で最も映える）。代替案＝エレクトリックマゼンタ `oklch(66% 0.27 350)` / ネオンシアン `oklch(82% 0.15 200)`。Aurora（teal）と被らない色相を選ぶこと。
- surface (dark): 近黒・わずかに寒色 `oklch(15% 0.012 280)`（主役のモード）
- surface (light): 反転した明るい寒色グレー `oklch(97% 0.004 280)`
- text: 超高コントラスト（dark=近白 `oklch(96% 0.004 280)` / light=近黒 `oklch(20% 0.01 280)`、彩度ほぼ 0）
- mood: cool # 影・ボーダーを寒色寄りに

## Typography

- display: 大きく・大胆な幾何/コンデンス系サンス。既定 "Space Grotesk"（700 を強めに使う）。よりインパクトが欲しければ @fontsource にある "Sora" / "Archivo" 等の追加提案も可（許可リスト外を使う場合は fonts に明記）。
- body: "Inter"
- mono: "JetBrains Mono"
- baseFontSize: 1.0625rem
- typeScale: 1.3 # dramatic。見出しの階層を強く＝大胆な display

## Shape & density

- density: comfortable # 暗い地は余白で呼吸させる
- radius: sharp # 硬いエッジでエディトリアルの緊張感

## Flags（構造選択・#390）

<!--
flags は `.nene-public` の data-* になり base CSS（public-site.css）が実装する＝DOM 不変。
語彙と効果は ../theme-flags.md。manifest の "flags" に列挙値で入れる。
-->

- feedLayout: magazine # フィーチャー＋グリッド（大きなヒーロー特集）
- cardStyle: framed # ダーク地でカード上端にアクセントの縁（ネオンエッジ）
- media: duotone # 画像をネオン着色のデュオトーン＝最も Noir らしい
- hero: fullbleed # 全幅のダークバンドで大胆に
- sectionRule: heavy # 見出し下に太いアクセント罫
- eyebrow: caps # mono の小型キャップス

## Assets（任意）

- hero: 既定（装飾画像なし・タイポ主体）。任意でネオングロー/グレインの装飾（外部 url 不可・同梱 or data URI / SVG）。
- logo: 既定（NeneMark 流用）。
- decoration: 微かなグロー・グレイン・ハイライトの方向性まで（やり過ぎ注意）。
- thumbnail（ピッカー用）: 16:7 のスクショ（light/dark どちらか代表 1 枚）。`frontend/public/theme-thumbnails/noir.webp` に置く想定（README 参照）。

## Approach（作り込み方針）

- **flags でかなり寄せられる**が、Noir の「大胆・ダーク・大型 display・グロー」は **bespoke `components.css`（L2）で版面を作り込む**のが主眼。お手本は `reference/aurora.components.css`（作り込み量）。
- tokens.css は配色・トークン、components.css で `.hero` / `.featured` / `.card` / `.section__head` / 見出しタイポの個性を出す。「色が暗いだけ」で終わらせない。
- 「JSON `flags` だけ（CSS なし）」も技術的には可能だが、Noir はインパクト重視のため components.css 推奨。

## References

- 既存 3 テーマ（consumer=暖色soft / aurora=寒色tech / reading=serif読み物）と被らない第 4 の軸＝**ダークファースト・大胆**。
- mood/radius/density/フォントの振れ幅は `examples/aurora.theme.md` `examples/ink.theme.md` を参照。

---

## 指示（ClaudeDesign 向け・受け入れ条件）

> 詳細の正本は `../theme-authoring.md` §3–§5 と handoff の `HANDOFF.md`。以下は Noir 固有の要点。

### 出力する bundle

```
noir/
├─ manifest.json     # public-theme.schema.json 準拠。id=noir / supportsModes:[light,dark] / fonts / tokens / flags（上記）/ description / author / version
├─ tokens.css        # [data-theme='noir'] と [data-theme='noir-dark'] の 2 ブロック
├─ components.css    # ★主役。既存クラスへの上乗せのみ（新規 DOM なし）
├─ assets/           # 任意。外部 url 不可（同梱 or data URI）
└─ screenshots/      # preview-light / preview-dark（＋できれば mobile）。1 枚は 16:7 のピッカー用サムネ候補に
```

### 守ること

- CSS は **`.nene-public[data-theme='noir'|'noir-dark']`**（または `^='noir'` で light/dark 一括）にスコープ。
- **DOM は変えない**・**任意 JS なし**・**外部 `url()`/`@import` 禁止**（data URI / バンドル相対のみ）・`javascript:`/`expression()` 等の危険構文禁止。
- **Knob のみ決め、Derived は契約 §3 の派生式で生成**（accent→hover/weak/on-accent、surface→raised/overlay/sunken/border、baseFontSize×typeScale→`--text-*`、density→`--space-*`、radius→`--radius-*`）。
- 色は **OKLCH ＋ `color-mix`** 基本。
- **コントラスト AA**（`text-primary`・`on-accent` が背景に 4.5:1 以上）を **light/dark 双方**で。ネオン差し色の上の文字色に注意。

### レンダリング確認・検査

- 正準マークアップ `reference/preview.html` に `public-site.css` ＋ 自分の `tokens.css`/`components.css` を当てて、light/dark 両方を目視確認（flags の data-* も `#site` に付けて確認できる）。
- 取り込み後、人間が `npm run validate:themes --prefix frontend`（CI ゲートと同じ）で検査。登録手順は `../theme-authoring.md` §6。
