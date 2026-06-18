# 公開サイト ヘッダー — グリッドモデル＆プリセット仕様

> 正本ドキュメント（ドラフト）。公開サイトヘッダーを **「行×ゾーンのグリッド」＋「要素パレット（ON/OFF・配置）」** という有限の文法で定義し、**汎用エンジン（base CSS）が1回だけ実装**する。WordPress のヘッダービルダー（Astra / Kadence / Blocksy）が収束した「3行グリッド＋ドラッグ要素」モデルの NeNe 版。
>
> - 親: [`theme-flags.md`](./theme-flags.md)（スタイルフラグ機構を踏襲・拡張）。関連: [`public-theme-contract.md`](./public-theme-contract.md)（tokens）/ [`public-theme.schema.json`](./public-theme.schema.json)（manifest スキーマ）。
> - 実装対象 DOM: `PublicSiteShell.tsx` の `<header class="hd">`（現行クラスフック `.hd` / `.hd__in` / `.hd__nav` / `.brand` を保つ）。
> - 設計原則: **DOM は極力変えず、構造選択は `data-*` ＋ 要素 ON/OFF で表現**。語彙に収まらない唯一無二デザインは escape hatch（bespoke）に逃がす。

---

## 1. なぜ「パターンは少ない」のか — 文法の核

世の中の Web ヘッダー／WP テーマヘッダーは見た目は無限だが、**生成文法は有限の小さな積**に分解できる:

```
ヘッダー = コンテナ（最大3行 × 各行3ゾーン = 9セル）
         × 要素パレット（11種、各セルへ配置・ON/OFF）
         × 行モディファイア（揃え / 幅 / sticky / 透過 / 背景）
```

「classic」「centered」「two-row」等の“パターン名”は、**この9セルへの定番の詰め方（プリセット）**にすぎない。電話番号・住所・営業時間といったインフォ要素も「新パターン」ではなく **Top 行に置くインフォ要素** として最初から枠内に入る。

## 2. コンテナモデル（行×ゾーン）

| 行 | data 属性（有効化） | 左ゾーン | 中央ゾーン | 右ゾーン | 既定の用途 |
| --- | --- | --- | --- | --- | --- |
| **Top（トップバー）** | `data-header-topbar` | inline | inline | inline | 電話/メール/住所・営業時間・告知・SNS・言語切替 |
| **Main（メイン）** | （常時） | ブランド | nav | アクション群 | ロゴ・ナビ・検索/テーマ/CTA |
| **Bottom** | `data-header-bottom` | inline | inline | inline | nav 下段（フル幅メニュー）等 |

- **Main 行は必須**。Top / Bottom は任意（既定 OFF）。多くのサイトは Main 1 行のみ。
- 各行は内部に **左 / 中央 / 右の3ゾーン**を持つ。空ゾーンは潰れる（grid が詰まる）。
- モバイルでは Top/Bottom の扱いを §6 のルールで畳む。

## 3. 要素パレット（セルに入る部品）

各要素は**配置先ゾーン**と**表示 ON/OFF** を持つ。ON/OFF は当面の最重要ニーズ（検索・テーマ切替を隠す）をそのまま満たす。

| 要素 | キー | 既定 | 既定ゾーン | 備考 |
| --- | --- | --- | --- | --- |
| Logo / ブランドマーク | `brand` | ON | Main 左 | `.brand`（ロゴ画像 or `NeneMark`） |
| サイト名 | `siteName` | ON | brand 内 | brand に内包 |
| **タグライン（キャッチコピー）** | `tagline` | OFF | brand 内 | `site.tagline`。`.brand__tag` |
| Primary nav | `primaryNav` | ON | Main 中央 | タイプ別リンク（現行 `navLinks`） |
| Secondary nav | `secondaryNav` | OFF | Top 右 等 | 補助メニュー（規約/会社情報等、将来） |
| **検索** | `search` | ON | Main 右 | `.hd__search`。**OFF で非表示** |
| **テーマ切替** | `themeToggle` | ON | Main 右 | `ThemeSwitch`。**OFF で非表示** |
| CTA ボタン | `cta` | OFF | Main 右 | ラベル＋URL（将来。問い合わせ/購読等） |
| SNS | `social` | OFF | Top 右 | アイコン群（将来） |
| **電話 / メール** | `contact` | OFF | Top 左 | インフォ要素。`tel:` / `mailto:` |
| **住所 / 営業時間** | `infoText` | OFF | Top 中央 | 自由テキスト（HTML 自由欄相当、サニタイズ） |
| 言語切替 | `langSwitch` | OFF | Top 右 | 多言語化時（将来） |
| ハンバーガー | `menu` | ON | Main 右 | モバイル時ドロワー起動（`.hd__menu`） |

> **当面の実装範囲（Phase A）**: `search` / `themeToggle` / `tagline` の ON/OFF と、Top バーの `contact` / `infoText`。残りは将来スライス。

## 4. スタイルフラグ語彙（ヘッダー拡張）

[`theme-flags.md`](./theme-flags.md) §2 の `ThemeFlags` / `FLAG_DEFS` に以下を**追記**する。各フラグは `.nene-public` の `data-*` 属性になり、base CSS が実装（`DOM 不変`）。値は列挙。

| フラグ | data 属性 | 値 | 効果（base CSS が実装） |
| --- | --- | --- | --- |
| `headerLayout` | `data-header` | `classic` \| `nav-right` \| `centered` \| `centered-stack` \| `two-row` \| `minimal` | §5 の骨格プリセット。9セルへの定番配置を選択 |
| `headerWidth` | `data-header-width` | `boxed` \| `full` | ヘッダー内容の幅（`--content-w` に従う or フル幅） |
| `headerDensity` | `data-header-density` | `compact` \| `regular` \| `tall` | 行高・上下パディング |
| `headerSticky` | `data-header-sticky` | `none` \| `sticky` \| `shrink` | スクロール追従（shrink=縮小追従） |
| `headerSurface` | `data-header-surface` | `solid` \| `transparent` \| `overlay` | 背景。overlay=hero に重ね、スクロールで solid 化 |
| `headerNavAlign` | `data-header-nav` | `start` \| `center` \| `end` | Main 中央ゾーンの nav 寄せ |

要素の ON/OFF は §3 のキーに対応する真偽フラグ（`data-header-search="hide"` 等）か、`theme_overrides` の `header` オブジェクトで持つ（§7）。実装方式は §7 で確定。

### base CSS 例（イメージ）

```css
/* 骨格: centered = ブランド中央・nav 下段 */
.nene-public[data-header="centered"] .hd__in { flex-direction: column; align-items: center; }
.nene-public[data-header="centered"] .brand { justify-content: center; }

/* 要素 OFF */
.nene-public[data-header-search="hide"] .hd__search { display: none; }
.nene-public[data-header-theme="hide"]  .themesw    { display: none; }

/* 挙動: 透過オーバーレイ */
.nene-public[data-header-surface="overlay"] .hd { position: absolute; background: transparent; }

/* 密度 */
.nene-public[data-header-density="compact"] .hd__in { padding-block: var(--space-xs); }
```

## 5. プリセット骨格カタログ（v1）

`headerLayout` の各値が表す 9 セルの詰め方。実世界ヘッダーの 95% 超をカバー（残りは bespoke）。

| 値 | 骨格 | ブランド | nav | アクション | 代表 |
| --- | --- | --- | --- | --- | --- |
| `nav-right` | 1行 | 左 | 右 | 右 | ブログ・SaaS（**現行の既定**・実装済み） |
| `classic` | 1行・3分割 | 左 | 中央 | 右 | メディア・EC（実装済み） |
| `minimal` | 1行 | 左 | （ドロワーのみ） | 右 | ポートフォリオ・LP（実装済み） |
| `centered` | 1行・対称 | 中央 | 左右分割 | 右 | ブランド・ファッション（B.2 予定） |
| `centered-stack` | 2行・中央 | 中央上 | 下段中央 | 右上 | マガジン・高級系（B.2 予定・Bottom行要） |
| `two-row` | 2行・左 | 左上 | Bottom 行フル幅 | 右上 | 大型サイト・官公庁（B.2 予定・Bottom行要） |

各骨格に対し、`headerWidth` / `headerDensity` / `headerSticky` / `headerSurface` / `headerNavAlign` と要素 ON/OFF を掛けて最終形を作る。Top バー（`data-header-topbar`）は全骨格に付加可能。

> **実装状況（Phase B.1）**: 単一行で成立する `nav-right` / `classic` / `minimal` ＋ `headerNavAlign` / `headerDensity` を base CSS 実装済み。DOM は `.hd__nav`（プライマリnav）と `.hd__actions`（検索/テーマ/メニュー）に分離し3ゾーン flex 化。2行系（`centered-stack` / `two-row`）と対称 `centered`、`headerWidth` は Top/Bottom 行コンテナを要するため **Phase B.2** へ。

## 6. モバイル時の畳み方ルール

- **Main 行**: nav は `≤ {ブレークポイント}` でハンバーガー＋ドロワーに集約（現行挙動を維持）。検索/テーマ切替はドロワー内に移す（現行 `drawer` 末尾に `ThemeSwitch` を置く実装を踏襲）。
- **Top バー**: 既定でモバイル非表示（`contact`/`infoText` は場所を取るため）。`data-header-topbar-mobile="keep"` で 1 行だけ残すオプション。
- **Bottom 行（two-row のフル幅メニュー）**: モバイルではドロワーに統合（独立行を作らない）。
- **タグライン**: モバイルでは既定で非表示（brand の縦積みを避ける）。

## 7. データ形（manifest ＆ overrides）

ヘッダー設定は theme 既定（manifest）と管理者上書き（`theme_overrides`）の両方に持てる。token ノブ／既存 flags と同じ「merge: 上書き優先」フロー（[`theme-flags.md`](./theme-flags.md) §2 適用フロー）。

```jsonc
{
  "flags": {
    "headerLayout": "classic",
    "headerSticky": "shrink",
    "headerNavAlign": "center"
  },
  "header": {                 // 要素 ON/OFF と配置・インフォ内容
    "topbar": true,
    "elements": {
      "tagline": false,
      "search": true,
      "themeToggle": false,    // ← 当面ニーズ: テーマ切替を隠す
      "contact": { "phone": "03-xxxx-xxxx", "email": "info@example.com" },
      "infoText": "平日 10:00–18:00 / 東京都…"
    }
  }
}
```

- `flags` の `header*` は §4 の enum のみ（[`public-theme.schema.json`](./public-theme.schema.json) に追記、Ajv で検証）。
- `header.elements.*` の自由テキスト（`infoText`）はサニタイズ必須（既存の override CSS と同様、既知の安全な出力のみ）。
- ON/OFF は `header.elements` を正とし、解決後に `.hd` 直下要素を条件レンダリング＋必要分を `data-*` にも反映。

## 8. 既存実装との関係・移行

- 現行 `PublicSiteShell.tsx` の `<header class="hd">` は **`classic` ＋ search ON ＋ themeToggle ON** に相当 → 既定値をこの組にすれば**見た目は不変**で互換維持。
- クラスフック（`.hd` / `.hd__in` / `.hd__nav` / `.brand` / `.hd__search` / `.themesw` / `.hd__menu` / `drawer`）は保持。CSS は `public-site.css` の Style flags ブロック近傍に「Header」セクションを追加し集約。
- `tagline` は `site.tagline`、`logo` は `site.logo` を既に受領済み（`public-site-context.ts`）。`contact`/`infoText` は site 設定 or theme override に新設。

## 9. 甘くない点（明記）

- **エンジン初期コスト**: 6 骨格 × 密度/幅/sticky/surface × light/dark × レスポンシブの掛け算を base CSS で先に作り込む。`centered-stack` / `two-row` の 2 行系はモバイル集約が一番手間。
- **組合せの破綻**: 自由組合せで不格好化 → 骨格ごとに推奨モディファイアをプリセットしてガード（[`theme-flags.md`](./theme-flags.md) §7 と同じ方針）。
- **語彙の天井**: 縦置きサイドヘッダー等の例外は flags に収まらない → bespoke escape hatch。
- **インフォ要素の安全性**: `infoText` 等の自由入力は XSS 面。サニタイズ／許可タグ限定を契約に明記。

## 10. 実装スライス（提案）

1. ⬜ 本ドキュメント＋ `header*` フラグ・`header` オブジェクトのスキーマ追記。
2. ⬜ **Phase A（即効）**: 要素 ON/OFF（`search` / `themeToggle` / `tagline`）＋ Top バー（`contact` / `infoText`）。`PublicSiteShell` の条件レンダリング＋ base CSS。
3. ⬜ **Phase B（骨格）**: `headerLayout` 6 骨格 ＋ `headerNavAlign` / `headerDensity` / `headerWidth` の base CSS。
4. ⬜ **Phase C（挙動）**: `headerSticky`(shrink) / `headerSurface`(overlay) — JS でスクロール状態を `data-*` に反映。
5. ⬜ カスタマイザ UI（`ThemeCustomizeView`）: 骨格プルダウン＋要素トグル＋インフォ入力。simple/pro 分割は既存方針に従う。
6. ⬜ validator の enum 検証（`public-theme.schema.json` ＋ Ajv）。

## 11. プリセット選定のための実データ（任意・補助）

骨格カタログ（§5）の **文法**は WP ビルダーの収束で確定済み。スクレイピングは文法発見ではなく **「どの詰め方が実際に多いか」の頻度データ**取得に使い、デフォルト提供プリセットの優先順位付けに充てる:

- WordPress.org ブロックパターン「ヘッダー」カテゴリの巡回（分類済み・取得可能を確認済み）。
- 主要テーマのスターターテンプレ デモを 30–50 サンプリングし、§2 の 9 セルへ正規化集計。
- 注意: JS 描画デモは構造復元が不安定 → パターンディレクトリ＋ビルダー公式の要素表を主軸に。

> 結論: スクレイピングは**必須ではない**が、プリセットの取捨選択と既定値決定の**裏付け**として有効。文法仕様（本書）が先、頻度データは後付けで精度を上げる位置づけ。
