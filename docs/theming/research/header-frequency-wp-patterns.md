# ヘッダー意匠 頻度調査 — WordPress.org ブロックパターン（Issue #419 Phase B）

> 目的: `docs/theming/header-patterns.md` の **6 骨格プリセット（§5）** と **要素パレット（§3）** について、「文法のどの詰め方が実際に多いか」を実データで裏付け、提供デフォルトの優先順位を決める。
>
> 正規化モデル: 本調査の全分類は `header-patterns.md` の **3 行 × 3 ゾーン（§2）** と **6 骨格（§5: classic / nav-right / centered / centered-stack / two-row / minimal）** に射影している。

---

## 1. 方法論とサンプルサイズ

### ソース

- WordPress.org ブロックパターンディレクトリ「Headers」カテゴリ（カテゴリ ID = **5**）。
  - カテゴリ UI: https://wordpress.org/patterns/categories/header/
  - REST API: `https://wordpress.org/patterns/wp-json/wp/v2/wporg-pattern?pattern-categories=5`

### 取得手段（とハマりどころ）

- **カテゴリ UI（HTML）と検索 UI は JS 描画**で、`WebFetch`／静的取得では **キュレーションされたコア約 9–10 件しか返らず、ページネーション（`?page=N`）も効かない**。これは静的取得の限界として記録する（推測で埋めず実取得のみ採用）。
- 代わりに **公式 WP REST API（`wporg-pattern` ポストタイプ）** を使用。`X-WP-Total: 23,654 / TotalPages: 237`。ただし大半は **同一パターンの多言語ロケール複製**（`-nl_nl` `-de_de_formal` `-kir`〔キルギス語〕等）でノイズが極大。
- ロケール接尾辞を正規表現で除去し **canonical（英語原本）スラッグのみ**に正規化。API 1〜15 ページ＋カテゴリ UI のコア 11 件を統合。

### 分類方法

- 各パターンの **`content.rendered`（実 HTML）** を取得し、`wp-block-navigation` / `wp-block-site-title` / `wp-block-site-logo` / `wp-block-search` / `wp-block-social-links` / `wp-block-button` / `wp-block-site-tagline` 等の **ブロック CSS クラス**を検出。
- レイアウトは `is-content-justification-{space-between|center|right|left}` クラスの **出現順とブランド／nav の DOM 位置**から、ブランド配置（左/中央）・nav 配置（インライン右/中央/下段）を判定し、6 骨格へ射影。
- パターンディレクトリは **静的マークアップ**なので構造判定は信頼できる（§ caveat）。

### サンプル内訳

| 区分 | 件数 |
| --- | --- |
| 取得した distinct（重複/ロケール除去後）canonical パターン | **46** |
| └ **真のヘッダー（site header / masthead = nav か brand を持つナビバー）** | **22** |
| └ ヒーロー/バナー section（nav も brand も持たない見出し帯。骨格集計から除外） | 24 |

> **重要な所見**: WP「Headers」カテゴリは **半分が実質ヒーロー/バナー section**（巨大見出し＋ボタン＋カバー画像）で、サイトナビゲーションヘッダーではない。骨格頻度は **真のヘッダー 22 件**を母数に算出する（ヒーロー群は §4 で別掲）。

---

## 2. 骨格頻度（6 プリセット分布）

母数 = 真のヘッダー **22 件**。

| 骨格（§5） | 件数 | 割合 | 構造（WP 実装での現れ方） |
| --- | --- | --- | --- |
| **classic**（1行・3分割: 左/中央/右） | **11** | **50%** | ブランド左 ＋ nav ＋ **独立した右アクションゾーン**（CTA/検索/SNS）。`space-between` 外側＋右クラスタに nav 以外の部品 |
| **nav-right**（1行: 左 / 右） | **10** | **45%** | ブランド左 ＋ nav を右に寄せるのみ（追加アクションなし）。WP コアの `simple-header` 系の典型 |
| **centered**（1行・対称: 中央ブランド） | **1** | **5%** | `centered-header-with-logo`（ロゴ中央＋ nav 左右/下） |
| centered-stack（2行・中央） | 0 | 0% | 該当なし |
| two-row（2行・左 ＋ Bottom フル幅 nav） | 0 | 0% | 該当なし（カテゴリ内に純粋な2行masthead無し） |
| minimal（1行・ドロワーのみ） | 0 | 0%* | *`site-title-and-menu-button` 等は nav-right に含めて計上（メニューボタン＝モバイル挙動でドロワー化するが、構造上は1行 左/右） |
| other / exotic | 0 | 0% | 真ヘッダー側に骨格外なし。exotic は §4 のヒーロー群 |

**読み方**: WP の真ヘッダーは **「ブランド左 ＋ 残りすべて右」**にほぼ収束（classic + nav-right = **95%**）。classic と nav-right の差は「右側に nav 以外の独立アクション（CTA/検索/SNS）があるか」だけ。`centered` は希少、`centered-stack` / `two-row` はこのカテゴリでは実質ゼロ。

> classic / nav-right の境界判定はヒューリスティック（右クラスタに `button`/`search`/`social` があれば classic）であり、±数件の揺れはあり得る。両者を合算した **「ブランド左・1行」family = 95%** は揺るがない。

---

## 3. 要素出現率（パレット §3）

母数 = 真のヘッダー **22 件**。`wp-block-*` クラス検出ベース。

| 要素（§3 キー） | 出現 | 率 | 既定（§3）との整合 |
| --- | --- | --- | --- |
| primaryNav（`wp-block-navigation`） | 22/22 | **100%** | ON ✓（masthead の定義上） |
| brand: logo（`site-logo`） | 15/22 | **68%** | ON ✓ |
| brand: siteName（`site-title`） | 13/22 | **59%** | ON ✓（logo 無し時のフォールバック） |
| cta（`wp-block-button`） | 9/22 | **41%** | 既定 OFF だが **想定より高頻度** |
| tagline（`site-tagline`） | 3/22 | **14%** | 既定 OFF ✓（低頻度を裏付け） |
| social（`social-links`） | 2/22 | **9%** | 既定 OFF ✓ |
| search（`wp-block-search`） | 1/22 | **5%** | 現行 ON 既定だが **WP では極めて稀** |
| contact（phone/email）/ infoText / langSwitch / Top バー | 0/22 | **0%** | 既定 OFF ✓（WP コアにトップバー文化が無い） |

**所見**:
- ブランド（logo か title のどちらか）は実質 100%。**logo 優先・title フォールバック**という現行設計と一致。
- **CTA ボタンが 41%** と高い。`header-patterns.md` §3 では `cta` 既定 OFF だが、提供プリセット側で「CTA あり classic」を上位に置く価値がある。
- **検索は 5%、Top バー系（contact/infoText/langSwitch）は 0%**。検索を Main 右に常時 ON にしている NeNe 現行既定は、WP 母集団では少数派 — テーマ/サイト種別で出し分ける余地。
- tagline 14% / social 9% は §3 の OFF 既定と整合（低頻度オプション扱いで正しい）。

---

## 4. Exotic / 骨格外リスト

6 骨格に収まらない、または masthead でない（ヒーロー/バナー section）パターン。**骨格集計からは除外**。NeNe では §5 ではなく **bespoke escape hatch / 別コンポーネント（hero block）**で扱うべき領域。

| パターン | 一言説明 |
| --- | --- |
| large-header-with-text-and-a-button | 巨大見出し＋ボタンのみ。nav 無し（ヒーロー帯） |
| large-header-with-left-aligned-text | 左寄せ巨大テキストのヒーロー、nav 無し |
| header-with-hero-image / header-inside-full-width-background-image | 上部に細い nav バー、その下にフルブリードのカバー画像（ヘッダー＋ヒーロー複合）→ nav 部は classic/nav-right、画像帯は別レイヤ |
| header-with-fullwidth-background-image | カバー画像＋見出しのみ、ナビ無し |
| header-with-image-and-accordion | 画像＋アコーディオン（FAQ的）。ヘッダー文法外 |
| modern-hero-with-stats-call-to-action | ヒーロー＋数値スタッツ＋CTA。section コンポーネント |
| split-layout-with-rounded-image-frame-and-bold-typography | 左右分割ヒーロー（画像＋大見出し） |
| hero-section-17 / hero-section-with-header-2 / classic-hero-section / text-hero / about-me-banner / banner-section-design / simple-banner-section / call-to-action-banner-6 / landing-page-cover / bold-hero-section-with-headline-and-buttons / modern-header / creative-layout / hiring-steps / bicolor-header-with-image / black-and-white-list-in-column-with-images / blog-post-header / header-full-width-image-with-header-content-and-button | いずれも nav を持たないヒーロー/ランディング/バナー帯。「Headers」カテゴリに混在しているが site header ではない |
| simple-header-two-columns | 名称に反し nav/brand ブロック未検出（2カラムの帯）。masthead 判定外 |

> **構造的に純粋な `centered-stack`（ロゴ中央上＋ nav 下段中央の2行）と `two-row`（左上ブランド＋ Bottom フル幅 nav）は、このカテゴリのサンプル内に出現しなかった。** これは「マガジン/官公庁系」の骨格で、WP コア/コミュニティのブロックパターンより、Astra/Kadence 等のテーマスターターデモに多い（§ header-patterns.md §11 が示唆する別ソース）。本調査の範囲では 0%。

---

## 5. 推奨（デフォルト提供プリセットの優先順位）

1. **`classic` と `nav-right` を最優先・最上位の2デフォルトにする。** 両者で真ヘッダーの **95%** を占め、構造差は「右側に独立アクションゾーンがあるか」だけ。NeNe 現行既定 `classic` は妥当。`nav-right` を「アクション無しのシンプル版」として並置すれば WP 母集団をほぼ網羅できる。

2. **`classic` のサブプリセットに「CTA ボタン付き」を用意する。** CTA は **41%** と高頻度で、`header-patterns.md` §3 の `cta` OFF 既定のままだと多数派ニーズを取りこぼす。`elements.cta` を Main 右に置いた classic を提供プリセットの2番手に。

3. **検索の常時 ON 既定を見直す（テーマ/サイト種別で出し分け）。** `search` は WP 母集団で **5%** と極めて稀。NeNe 現行の「Main 右に search 常時 ON」はメディア/EC では妥当だが、ブログ/ブランド/LP 系の既定では OFF を検討。少なくともプリセット単位で search ON/OFF を変える。

4. **`centered-stack` / `two-row` は「低優先・後回しスライス」に格下げ。** 本カテゴリで **0%**。実装コストが高い2行系（§9 でモバイル集約が最重）に対し、需要の実証が弱い。`centered`（5%）も含め2行系は v1 では「対応はするが既定提供は最小限」でよい。エンジンの作り込み順は classic → nav-right → centered → (centered-stack/two-row) を推奨。

5. **Top バー（contact / infoText / langSwitch）は WP 母集団で 0%。** ただしこれは WP ブロックパターン文化にトップバーが無いだけで、ビジネスサイト/官公庁では一般的（§ header-patterns.md §3 が別途想定）。**「実装はするが既定 OFF」という現行方針は正しい**ことを本データが追認。優先度は骨格より下。

---

## 付録: 真ヘッダー 22 件の分類一覧

| パターン | 骨格 | 検出要素 | URL |
| --- | --- | --- | --- |
| centered-header-with-logo | centered | logo, title | https://wordpress.org/patterns/pattern/centered-header-with-logo/ |
| hero-section-with-header-2 | classic | logo, title, button | https://wordpress.org/patterns/pattern/hero-section-with-header-2/ |
| modern-gradient-landing-hero | classic | logo, button | https://wordpress.org/patterns/pattern/modern-gradient-landing-hero/ |
| header-6 | classic | social | https://wordpress.org/patterns/pattern/header-6/ |
| cool-stylish-header | classic | button | https://wordpress.org/patterns/pattern/cool-stylish-header/ |
| goblocks-header-blog1 | classic | logo, title, button | https://wordpress.org/patterns/pattern/goblocks-header-blog1/ |
| header-with-round-logo-and-socials | classic | logo, title, tagline, social | https://wordpress.org/patterns/pattern/header-with-round-logo-and-socials/ |
| header-with-intro-hero-and-feature-highlights | classic | logo, button | https://wordpress.org/patterns/pattern/header-with-intro-hero-and-feature-highlights/ |
| stylish-header | classic | button | https://wordpress.org/patterns/pattern/stylish-header/ |
| header-with-navigation-and-call-to-action | classic | button | https://wordpress.org/patterns/pattern/header-with-navigation-and-call-to-action/ |
| header-with-contact-button | classic | logo, button | https://wordpress.org/patterns/pattern/header-with-contact-button/ |
| header-with-search-and-buttons | classic | logo, search, button | https://wordpress.org/patterns/pattern/header-with-search-and-buttons/ |
| header-with-nav-sticky-background-and-quote | nav-right | logo, title | https://wordpress.org/patterns/pattern/header-with-nav-sticky-background-and-quote/ |
| dark-header-navigation | nav-right | logo | https://wordpress.org/patterns/pattern/dark-header-navigation/ |
| text-only-header-with-tagline | nav-right | title, tagline | https://wordpress.org/patterns/pattern/text-only-header-with-tagline/ |
| simple-header | nav-right | logo, title | https://wordpress.org/patterns/pattern/simple-header/ |
| simple-header-with-tagline | nav-right | logo, title, tagline | https://wordpress.org/patterns/pattern/simple-header-with-tagline/ |
| simple-header-with-dark-background | nav-right | logo, title | https://wordpress.org/patterns/pattern/simple-header-with-dark-background/ |
| header-with-hero-image | nav-right | logo, title | https://wordpress.org/patterns/pattern/header-with-hero-image/ |
| header-inside-full-width-background-image | nav-right | logo, title | https://wordpress.org/patterns/pattern/header-inside-full-width-background-image/ |
| site-title-and-menu-button | nav-right | title | https://wordpress.org/patterns/pattern/site-title-and-menu-button/ |
| header-with-large-font-size | nav-right | title | https://wordpress.org/patterns/pattern/header-with-large-font-size/ |

---

*調査実施日: 2026-06-18 / ソース: WordPress.org Pattern Directory（カテゴリ ID 5「Headers」）, REST API `wporg-pattern`。多言語ロケール複製を除去後の canonical 46 件、うち真ヘッダー 22 件を分類。骨格定義は `docs/theming/header-patterns.md` §5、要素は §3 に準拠。*
