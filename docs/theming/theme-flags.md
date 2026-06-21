# 公開サイト テーマ — スタイルフラグ＆プリセット（ハイブリッドモデル）

> 正本ドキュメント。テーマを **列挙パラメータ（tokens プリセット）＋スタイルフラグ** の **JSON データ**で定義し、**汎用エンジン（base CSS）が1回だけ実装**する。bespoke `components.css` は Pro の escape hatch として残す。
>
> - 親: #390（#367）。関連: [`public-theme-contract.md`](./public-theme-contract.md)（tokens）/ [`class-hooks.md`](./class-hooks.md)（クラス）/ [`public-theme.schema.json`](./public-theme.schema.json)（manifest スキーマ）。
> - WordPress `theme.json` / スタイルバリエーション相当。**DOM は変えない**（フラグは CSS で実装）。

---

## 1. 2層モデル

| 層                  | 形式                      | 誰が作る            | 役割                                         |
| ------------------- | ------------------------- | ------------------- | -------------------------------------------- |
| **A. プリセット層** | **JSON**（tokens＋flags） | ClaudeCode / 管理者 | 定番デザインを量産・調整。汎用エンジンが実装 |
| **B. escape hatch** | bespoke `components.css`  | ClaudeDesign        | 語彙外の唯一無二デザイン。Pro に隠す         |

A だけでも「WP の定番テーマ」級は出せる。語彙に収まらない時だけ B。

## 2. スタイルフラグ語彙（v1）

各フラグは `.nene-public` の **`data-*` 属性**になり、base CSS が実装する（`DOM は不変`）。値は**列挙**。未指定＝テーマ既定（フラグ無し＝現行の見た目）。

| フラグ        | data 属性      | 値                                              | 効果（base CSS が実装）                                                                                                            |
| ------------- | -------------- | ----------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| `feedLayout`  | `data-feed`    | `grid` \| `list` \| `magazine`                  | 最新フィードの版面。grid=均等カード / list=横長行（`.cardgrid`→1カラム化、`.card`を行化）/ magazine=フィーチャー＋グリッド（現行） |
| `feedColumns` | `data-feed-cols` | `auto` \| `1` \| `2` \| `3` \| `4`            | グリッドフィードの列数。auto=幅に応じた auto-fill（現行）/ 1〜4=固定列（デスクトップ/タブレットは尊重、スマホ幅 ≤430px で 1 列に畳む。4 は ≤620px で 2 列）。`feedLayout=list` 時は無効 |
| `cardStyle`   | `data-cards`   | `flat` \| `bordered` \| `shadowed` \| `framed`  | `.card` の意匠（枠/影/額装）                                                                                                       |
| `media`       | `data-media`   | `plain` \| `duotone` \| `grayscale` \| `framed` | `.card__media` / `.featured__media` / `.article__hero` の見せ方（`filter`/枠）                                                     |
| `hero`        | `data-hero`    | `standard` \| `fullbleed` \| `minimal`          | `.hero` 構図（minimal=アート省略・タイポ主体 / fullbleed=幅広）                                                                    |
| `sectionRule` | `data-rule`    | `none` \| `hairline` \| `heavy`                 | `.section__head` の区切り罫                                                                                                        |
| `eyebrow`     | `data-eyebrow` | `plain` \| `caps` \| `barred`                   | `.eyebrow` の表情（小型キャップス/バー）                                                                                           |
| `headerSearch`  | `data-header-search`  | `show` \| `hide` | ヘッダー検索アイコン（`.hd__search`）の表示。詳細は [`header-patterns.md`](./header-patterns.md) |
| `headerTheme`   | `data-header-theme`   | `show` \| `hide` | ヘッダーのテーマ切替（`.themesw`）の表示。ヘッダー/ドロワー両方に効く |
| `headerTagline` | `data-header-tagline` | `show` \| `hide` | ブランドのキャッチコピー（`.brand__tag`）の表示 |
| `headerLayout`  | `data-header`         | `nav-right` \| `classic` \| `centered` \| `minimal` | ヘッダー骨格。nav-right=既定（nav＋操作を右）/ classic=nav中央 / centered=brand中央＋nav下段 / minimal=インラインnav省略（メニューのみ） |
| `headerNavAlign` | `data-header-nav`    | `start` \| `center` \| `end` | `classic` 時の nav 位置（ブランド寄り/中央/操作寄り） |
| `headerDensity` | `data-header-density` | `compact` \| `regular` \| `tall` | ヘッダー行高（regular=現行 72px） |
| `headerWidth`   | `data-header-width`   | `boxed` \| `full` | ヘッダー内容の幅（boxed=現行 `--content-w` / full=フル幅） |
| `headerSticky`  | `data-header-sticky`  | `sticky` \| `none` | スクロール追従（既定=sticky・現行挙動 / none=固定しない） |
| `motionReveal`  | `data-motion-reveal`  | `off` \| `subtle` \| `standard` | スクロール進入時のフェードイン（モーション能力レイヤ #371）。第一者JSが `.card`/`.typecard`/見出しに適用、`prefers-reduced-motion` で無効・JS無効でも要素は表示。テーマは flag を宣言するのみ |
| `motionHeader`  | `data-motion-header`  | `static` \| `shrink` | スクロールで sticky ヘッダーをコンパクトに縮小（#371 S2）。第一者JSが sentinel + IntersectionObserver で検出し `data-shrunk` を付与、`prefers-reduced-motion` で無効。`headerSticky=sticky`（既定）前提 |

> ヘッダー構造の全体仕様（グリッド/プリセット/インフォ要素）は [`header-patterns.md`](./header-patterns.md) を参照。
> 追加フラグは本表に追記して語彙を拡張する。実装は base CSS（`public-site.css` 近傍の専用ファイル）に集約。

### 適用フロー

```
theme.manifest.flags ┐
                     ├─(merge: 上書き優先)→ resolved flags → .nene-public data-* 属性 → base CSS
admin overrides.flags ┘
```

- tokens（色/幅/余白…）は従来どおり CSS 変数。flags は構造選択で `data-*`。両者は独立に効く。
- base CSS 例:

```css
.nene-public[data-feed="list"] .cardgrid {
  grid-template-columns: 1fr;
}
.nene-public[data-feed="list"] .card {
  flex-direction: row;
  align-items: center;
  gap: var(--space-lg);
}
.nene-public[data-cards="bordered"] .card {
  border: 1px solid var(--color-border);
  padding: var(--space-md);
}
.nene-public[data-media="grayscale"] .card__media {
  filter: grayscale(1);
}
```

## 3. JSON 定義（manifest 拡張）

manifest に任意の `flags` を追加（[`public-theme.schema.json`](./public-theme.schema.json) に定義）:

```json
{
  "id": "myzine",
  "name": "My Zine",
  "supportsModes": ["light", "dark"],
  "tokens": { "light": { … }, "dark": { … } },
  "flags": { "feedLayout": "list", "cardStyle": "bordered", "media": "grayscale", "sectionRule": "hairline" }
}
```

- `flags` の各キーは §2 の enum のみ（バリデータが検証）。
- これだけで版面が変わる＝**CSS を書かずに JSON でテーマ量産**できる。

### カスタマイザ（`theme_overrides`）でも flags

管理者の上書き（`theme_overrides` JSON）にも `flags` を持てる（テーマ既定を上書き）。token ノブと同じ流儀。

## 4. simple / pro モード（カスタマイザ）

- **simple（基本・常時表示）**: 非エンジニアが最初に触る要素 — アクセント/背景/文字色・本文フォント・幅・密度・`feedLayout`/`cardStyle`。
- **pro（詳細・`<details>` 折りたたみ／既定で閉）**: 微調整 — 左右余白・角丸・文字サイズ・見出しスケール・`media`/`hero`/`sectionRule`/`eyebrow`。将来は生トークン細指定・bespoke `components.css` の導線もここに。
- 実装: `ThemeCustomizeView`。基本 `Stack` の下に `<details>` で詳細グループ。

## 5. バリデーション

- manifest の `flags` は **enum チェック**（不正値は reject）。`validate:themes` に組込み。
- bespoke `components.css`（B層）は従来どおりスコープ/安全性チェック。

## 6. 既存との関係・移行

- 既存 3 テーマ（consumer / aurora / reading）は bespoke `components.css`（B層）。当面そのまま。語彙が育ったら **フラグで再表現できる部分は JSON へ寄せ**、残りを Pro bespoke に。
- tokens 契約（[`public-theme-contract.md`](./public-theme-contract.md)）・アセット契約は不変。flags は**構造選択の追加レイヤ**。

## 7. 甘くない点（明記）

- **語彙の天井**: 本当に新規な版面は flags に収まらない → escape hatch（B）必須。
- **エンジンの初期コスト**: 全フラグ×バリアントの base CSS を先に作り込む（light/dark × レスポンシブの掛け算）。
- **組合せの破綻**: 自由組合せで不格好になりうる → プリセット/推奨組合せでガード。

## 8. 実装スライス（#390）

1. ✅ 本ドキュメント＋ `flags` スキーマ。
2. ✅ 汎用エンジン v1: `feedLayout`(grid/list) ＋ `cardStyle` を base CSS 実装＋ `flags`→`data-*` 配線。
3. ✅ フラグ拡充（media / hero / sectionRule / eyebrow）の base CSS。`public-site.css` の Style flags ブロックに集約。
4. flags UI ＋ validator enum：
   - ✅ カスタマイザに全6フラグのプルダウン（`ThemeCustomizeView`）。
   - ✅ validator の flags enum 検証（`public-theme.schema.json` の `flags` enum ＋ `additionalProperties:false`、Ajv 駆動）。
   - ✅ simple/pro 分割：基本ノブ（色/フォント/幅/密度/feedLayout/cardStyle）は常時表示、詳細（余白/角丸/文字サイズ/見出しスケール/media/hero/sectionRule/eyebrow）は `<details>` で折りたたみ（既定で閉）。
5. ⬜ ClaudeCode で JSON テーマ量産。
