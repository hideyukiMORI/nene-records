# 公開サイト テーマ契約（Public Theme Contract）

> 正本ドキュメント。公開サイト（consumer フロント）のテーマが満たすべき**トークン契約**と**アセット契約**を定義する。WordPress の `theme.json` 相当。
>
> - 親エピック: #367 / 本書: #368（Phase 0）
> - 検証用スキーマ: [`public-theme.schema.json`](./public-theme.schema.json)
> - スコープは**公開サイトのみ**（`.nene-public` / `[data-theme='consumer*']`）。管理 UI のトークンには一切影響させない。

---

## 1. 原則

1. **テーマ＝トークン値（＋既存クラスへの上乗せ CSS ＋アセット）**。`public-site.css` のコンポーネント定義は無改修。色・余白・フォント・角丸・影はすべて `var(--*)` 経由なので、トークンの差し替えだけで見た目が変わる。
2. **DOM 構造・マークアップはテーマの管轄外**（既存 Layout 軸 = `PublicLayouts.php` の責務）。テーマは構造を変えない。
3. **light / dark 両対応が必須**。各テーマは `[data-theme='<id>']`（light）と `[data-theme='<id>-dark']`（dark）の 2 セットを提供する。
4. **配信はサーバ生成 CSS / 静的 CSS のみ**。テーマは任意 JS を持ち込まない（インライン JS 不使用 = JWT が localStorage にあるための制約）。
5. **色は OKLCH + `color-mix`** を基本とする（既存 consumer テーマに準拠）。

## 2. トークン分類（3 区分）

| 区分                | 意味                                             | 誰が決めるか      |
| ------------------- | ------------------------------------------------ | ----------------- |
| **Knob（ノブ）**    | カスタマイザで管理者が調整できる上位パラメータ   | 管理者（Phase 2） |
| **Derived（派生）** | ノブから自動計算されるトークン（`color-mix` 等） | システム          |
| **Fixed（固定）**   | テーマが値を持つが管理者は触らない               | テーマ作者        |

ノブを最小化し、残りは派生・固定にすることで「壊れにくさ」と「量産しやすさ」を両立する。

---

## 3. カラートークン（light / dark 各セット）

`[data-theme='<id>']` / `[data-theme='<id>-dark']` に定義する。

| トークン                                       | 用途                            | 区分     | 派生元 / 備考                                   |
| ---------------------------------------------- | ------------------------------- | -------- | ----------------------------------------------- |
| `--color-surface`                              | 基本背景（紙）                  | Knob     | dark/light の base paper                        |
| `--color-surface-raised`                       | 一段持ち上げた面（カード）      | Derived  | surface から明度 +Δ                             |
| `--color-surface-overlay`                      | オーバーレイ/コード地           | Derived  | surface から                                    |
| `--color-surface-sunken`                       | 沈んだ面                        | Derived  | surface から                                    |
| `--color-text-primary`                         | 本文                            | Knob     | surface に対し AA 以上                          |
| `--color-text-muted`                           | 補助テキスト                    | Derived  | primary から明度寄せ                            |
| `--color-text-inverse`                         | 反転テキスト                    | Derived  | surface 反転                                    |
| `--color-border`                               | 罫線                            | Derived  | surface から                                    |
| `--color-border-strong`                        | 強い罫線                        | Derived  | border から                                     |
| `--color-focus-ring`                           | フォーカス輪郭色                | Derived  | accent から                                     |
| `--color-accent`                               | ブランド/アクセント             | **Knob** | 主要ノブ                                        |
| `--color-accent-hover`                         | ホバー                          | Derived  | accent の明度調整                               |
| `--color-accent-weak`                          | 淡いアクセント地                | Derived  | `color-mix(accent, surface-raised)`             |
| `--color-on-accent`                            | アクセント上の文字              | Derived  | accent に対し AA                                |
| `--color-brand-violet`                         | ロゴ専用色（UI アクセントと別） | Fixed    | ロゴのみ                                        |
| `--color-danger` / `--color-danger-hover`      | 危険                            | Fixed    |                                                 |
| `--color-ok` / `--color-warn` / `--color-info` | ステータス                      | Fixed    |                                                 |
| `--shadow-sm` / `--shadow-md` / `--shadow-lg`  | 影                              | Fixed    | dark はより深い                                 |
| `--shadow-focus`                               | フォーカスリング影              | Fixed    | ※現状グローバル継承。テーマで定義することを推奨 |
| `color-scheme`                                 | `light` / `dark`                | Fixed    | フォームコントロール等の OS 連動                |

### 派生ルール（Derived の計算）

- `--color-accent-hover` = light は accent を暗く（L −6 程度）/ dark は明るく（L +7 程度）。
- `--color-accent-weak` = `color-mix(in oklch, var(--color-accent) 12%(light)/16%(dark), var(--color-surface-raised))`。
- `--color-focus-ring` = accent と同 hue で彩度/明度を確保した値。
- surface 系の raised/overlay/sunken は base surface から明度オフセットで導出（light は上げ、dark は段階）。
- **アクセシビリティ必須**: `text-primary`/`on-accent` は背景に対し WCAG AA（4.5:1）以上。検証は Phase 3 バリデータで自動化。

---

## 4. タイポグラフィトークン（`.nene-public` ルート）

| トークン                               | 既定                       | 区分    | 備考                          |
| -------------------------------------- | -------------------------- | ------- | ----------------------------- |
| `--font-sans`                          | `'Inter','Noto Sans JP',…` | Knob    | 本文フォント                  |
| `--font-display`                       | `'Saira Semi Condensed',…` | Knob    | 見出しフォント                |
| `--font-chrome`                        | `'Space Grotesk',…`        | Knob    | ボタン/UI ラベル              |
| `--font-mono`                          | `'JetBrains Mono',…`       | Knob    | コード                        |
| `--text-overline` … `--text-display`   | §下表                      | Derived | baseFontSize × scale から導出 |
| `--leading-tight/heading/body`         | 1.07 / 1.16 / 1.68         | Fixed   |                               |
| `--weight-normal/medium/semibold/bold` | 400/500/600/600            | Fixed   | ※同梱ウェイトは 600 まで      |
| `--tracking-display/overline`          | -0.015em / 0.14em          | Fixed   |                               |

### 現行タイプスケール（既定値）

```
--text-overline 0.75rem    --text-h3 1.25rem
--text-meta     0.8125rem  --text-h2 clamp(1.5rem, 1.1rem + 1.6vw, 2.125rem)
--text-body-sm  0.9375rem  --text-h1 clamp(1.6rem, 1.2rem + 1.6vw, 2.35rem)
--text-body     1.0625rem  --text-display clamp(2.5rem, 1.6rem + 4.4vw, 4.85rem)
```

ノブ `baseFontSize`（既定 1.0625rem）と `typeScale`（既定 ≈1.18）から `--text-*` を再計算する。clamp の流動下限/上限も baseFontSize に比例。

---

## 5. スペース / レイアウトトークン

| トークン                    | 既定                                      | 区分                        |
| --------------------------- | ----------------------------------------- | --------------------------- |
| `--space-2xs … --space-2xl` | 0.25 / 0.5 / 0.75 / 1 / 1.5 / 2.5 / 4 rem | Derived（density ノブから） |
| `--space-section`           | `clamp(3.5rem, 2rem + 6vw, 7rem)`         | Derived                     |
| `--gutter`                  | `clamp(1.25rem, 0.5rem + 3vw, 2.5rem)`    | Fixed                       |
| `--measure`                 | `72ch`                                    | Knob（本文計測幅）          |
| `--content-w`               | `1180px`                                  | Fixed                       |

`density`（compact / cozy / comfortable）ノブが `--space-*` 全体を一括スケール。

## 6. 角丸 / モーショントークン

| トークン            | 既定                      | 区分                               |
| ------------------- | ------------------------- | ---------------------------------- |
| `--radius-sm/md/lg` | 6 / 12 / 20 px            | Knob（`radius`: sharp/soft/round） |
| `--radius-full`     | 9999px                    | Fixed                              |
| `--ease`            | `cubic-bezier(.4,0,.2,1)` | Fixed                              |
| `--dur-fast/normal` | 120ms / 220ms             | Fixed                              |

---

## 7. カスタマイザ ノブ一覧（Phase 2 が公開する上位パラメータ）

| ノブ                                    | 型                             | 影響するトークン                                  |
| --------------------------------------- | ------------------------------ | ------------------------------------------------- |
| `accent`                                | color                          | accent / -hover / -weak / -on-accent / focus-ring |
| `surface`                               | color（light/dark 各）         | surface / -raised / -overlay / -sunken            |
| `textPrimary`                           | color                          | text-primary / -muted（派生）                     |
| `fontDisplay` / `fontBody` / `fontMono` | font（許可リスト）             | `--font-display` / `--font-sans` / `--font-mono`  |
| `baseFontSize`                          | length(rem)                    | `--text-*`（再計算）                              |
| `typeScale`                             | number(ratio)                  | `--text-*`（再計算）                              |
| `density`                               | enum(compact/cozy/comfortable) | `--space-*`                                       |
| `radius`                                | enum(sharp/soft/round)         | `--radius-sm/md/lg`                               |
| `logo`                                  | asset(image/svg)               | ロゴ表示                                          |
| `heroImage`                             | asset(image)                   | hero 背景                                         |
| `background`                            | asset(image)                   | サイト背景（任意）                                |

- ノブ上書きは**テーマ別・light/dark 別**に Setting ストアへ保存し、解決後に `.nene-public { --… }` をサーバ生成 CSS で注入する。
- 画像系ノブは**メディアライブラリ**（オンデマンド派生・#299）連携。

---

## 8. アセット契約

テーマは CSS だけでなく画像 / SVG を伴う。後付けを避けるため Phase 0 で定義する。

### 8.1 アセットスロット

| スロット     | 用途                             | 描画先       | light/dark 別 |
| ------------ | -------------------------------- | ------------ | ------------- |
| `preview`    | テーマ選択カードのスクショ       | 管理画面のみ | 可            |
| `hero`       | hero 背景画像                    | 公開サイト   | 可            |
| `background` | サイト背景（任意）               | 公開サイト   | 可            |
| `logo`       | ブランドマーク                   | 公開サイト   | 可            |
| `decoration` | 装飾シェイプ/テクスチャ/SVG      | 公開サイト   | 可            |
| `icons`      | テーマ独自アイコンセット（任意） | 公開サイト   | —             |

### 8.2 参照経路（この 2 つに限定）

テーマはマークアップを持たないため、アセットは次の経路でのみ参照する。

1. **CSS** の `background-image` / `mask-image`
   - `background-image: url("data:image/svg+xml,…")` … 装飾背景（色は焼き付く）。
   - `mask-image: url(…)` + `background: var(--color-accent)` … 単色 SVG を**テーマ色で recolor**。
   - data URI またはテーマバンドル**相対 URL**のみ。
2. **manifest のアセット URL** を第一者コンポーネント（hero 等）が読む。

### 8.3 配信 / 解決

- ビルトインテーマ: frontend 静的ビルド（Vite がハッシュ付与）。
- インポート / 第三者テーマ: **メディアライブラリに格納し自オリジンから配信**。manifest はアセットマップ（スロット → URL/相対パス、light/dark 別）を持つ。

### 8.4 セキュリティ規約

- **外部 `url()` / 外部 `@import` 禁止**（hotlink 不可。Phase 3 バリデータで強制）。
- 第三者の **SVG は必ずサニタイズ**: `<script>`・`on*` ハンドラ・外部参照（`xlink:href` 等）を除去。**インライン DOM 投入は禁止**（`<img>` / `background` / `mask` のみ）。
- アセットの**サイズ上限**を設ける（例: ラスタ画像 ≤ 1MB、SVG ≤ 64KB）。

---

## 9. テーマ bundle 形式（Phase 1/3 で利用）

```
<theme-id>/
├─ manifest.json        ← §10 スキーマ準拠（id/name/fonts/tokens/assets…）
├─ tokens.css           ← [data-theme='<id>'] と [data-theme='<id>-dark']
├─ components.css        （任意・既存クラスへの上乗せのみ。新規 DOM 構造は持たない）
├─ assets/              ← hero / logo / decoration / icons …
└─ screenshots/         ← preview（light/dark/mobile）
```

## 10. 検証スキーマ

`manifest.json` は [`public-theme.schema.json`](./public-theme.schema.json) で検証する。CSS（tokens.css / components.css）は Phase 3 バリデータで以下を検査:

- `.nene-public` / `[data-theme='<id>*']` スコープ強制（スコープ外セレクタ禁止）。
- 外部 `url()` / `@import` 禁止、危険プロパティ排除。
- 必須トークンの網羅（§3〜6）と light/dark 両セット存在。
- コントラスト AA 検証（text/on-accent）。

---

## 付録 A. 既存 consumer テーマの位置づけ

現行 `consumer-brand.css`（`[data-theme='consumer'/'consumer-dark']`）＋ `public-site.css` のスケール定義が、本契約の**リファレンス実装（ビルトイン既定テーマ）**。Phase 1 でこれを本契約の bundle 形式へ再表現する（最小例）。
