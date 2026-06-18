# ヘッダー頻度調査 — 商用 WP ヘッダービルダー横断

> Issue #419 Phase B 補助データ。`docs/theming/header-patterns.md`（§2 行×ゾーン / §3 要素パレット / §5 6 骨格）への正規化集計。
> 目的: 文法発見ではなく「どの詰め方／要素が業界で標準か」を定量化し、デフォルト提供プリセット（骨格セット）と既定モディファイア（sticky/overlay/topbar）の優先順位を裏付ける。
>
> 調査手段: WebSearch（ビルダー公式 docs を主軸）。WebFetch は本環境で権限不可だったため、公式 docs の検索サマリと GitHub ソース参照で代替。動的 JS デモは構造復元が不安定なため不採用（§11 方針どおり）。
> 調査日: 2026-06。

## 正規化マップ（本書の語彙）

- 行: **Top**（`data-header-topbar`）/ **Main**（常時）/ **Bottom**（`data-header-bottom`）。
- 6 骨格（§5）: `classic`（左ブランド・中央 nav・右アクション）/ `nav-right`（左ブランド・右 nav）/ `centered`（ブランド中央・nav 左右分割）/ `centered-stack`（2 行・ブランド中央上・nav 下段）/ `two-row`（2 行・左ブランド・Bottom フル幅 nav）/ `minimal`（左ブランド・nav ドロワーのみ）。
- 要素（§3）: `brand` / `primaryNav` / `secondaryNav` / `search` / `themeToggle` / `cta`(button) / `social` / `contact`(phone/mail) / `infoText`(HTML/住所/時間) / `langSwitch` / `menu`(ハンバーガー)。
- モディファイア（§4）: topbar / sticky(shrink) / surface=transparent|overlay。

凡例: ● 標準（既定で提供／第一級）, ○ 提供あり（Pro やオプション扱い含む）, − 非対応／不明。

---

## 1. ビルダー別サマリ

### Kadence（Kadence Theme ヘッダービルダー）
- **行モデル**: Top / Main / Bottom の 3 行。各行に **左 / 中央 / 右**の 3 配置エリア → 本書の 9 セルと完全一致。
- **要素**: Logo, Primary Nav, Secondary Nav, Search, Button, Social, HTML, Cart（無料版に同梱）。Pro で追加項目。→ palette と高一致。
- **行幅**: Standard / Fullwidth / Contained（= `headerWidth` boxed/full ＋背景幅の区別）。
- **骨格マップ**: Search を Main 中央、Logo を Main 左／nav を中央〜右に置く「Classic Header」が既定 → `classic`。配置を左ブランド・右 nav にすれば `nav-right`。Top 行で `centered-stack`/`two-row` 表現可。
- **Top バー**: ● Top 行が第一級（連絡先・告知・SNS の定番置き場）。
- **Sticky / Transparent**: ○ 行単位の sticky・transparent 対応（Advanced Header/Nav ブロックで強化）。
- 出典: kadencewp.com/help-center docs（[Customize Header](https://www.kadencewp.com/help-center/docs/kadence-theme/how-to-customize-the-kadence-header/), [Editing a Row](https://www.kadencewp.com/help-center/docs/kadence-theme/editing-a-row-in-the-header/), [Classic Header Items](https://www.kadencewp.com/help-center/docs/kadence-theme/kadence-classic-header-items/)）。

### Astra（Header Footer Builder, 3.0+）
- **行モデル**: Above / Primary / Below の 3 セクション = Top / Main / Bottom。各セクションに任意要素を配置。
- **要素**: Logo, Primary Menu, Secondary Menu, Search, Button, HTML, Social, Widget, Account, WooCommerce/EDD Cart。Pro で Sticky・Language Switcher 等。→ palette ほぼ全網羅（`langSwitch` は Pro）。
- **ロゴ位置プリセット**: Logo Left（既定・最頻）/ Logo Center（中央上に積む）/ Logo Right。→ それぞれ `classic`/`nav-right`・`centered-stack`/`centered`・（右ロゴ稀）に対応。スターターテンプレ既定は「ロゴ左・メニュー右」= `classic`/`nav-right`。
- **Top バー**: ● Above Header が secondary menu / social / CTA / 告知 HTML の定番置き場。
- **Sticky / Transparent**: ○ いずれも Astra Pro。Transparent Header はページ単位指定可。
- 出典: [Header Builder Options](https://wpastra.com/docs/header-builder-options/), [Elements in Header/Footer Builder](https://wpastra.com/docs/elements-in-header-footer-builder/), [Above Header](https://wpastra.com/docs/above-header-section/), [Centered Logo Header](https://wpastra.com/docs/create-header-with-centrally-positioned-logo/)。

### Blocksy（Header Builder）
- **行モデル**: 3 行（top / main / bottom）にドラッグ&ドロップ。行ごとに full width / 行高 / 背景・blur・border・shadow。
- **要素**: Logo, Menu, Search, Button, Cart, Social, HTML, Widget Area, Content Block 等。HTML 要素あり（= `infoText`/`contact` 相当）。
- **骨格マップ**: 既定はロゴ左・nav（→ `classic`/`nav-right`）。Top 行追加で 2 行系・centered-stack。
- **Top バー**: ● Top 行が第一級。
- **Sticky / Transparent**: ○ 「Headers」タブで sticky・transparent を設定（Pro で条件付きヘッダー複製）。
- 出典: [Header Builder Elements](https://creativethemes.com/blocksy/docs/header-elements/header-builder-elements/), [Sticky Header](https://creativethemes.com/blocksy/docs/header-builder/sticky-header/), [HTML element](https://creativethemes.com/blocksy/docs/header-elements/html/)。

### GeneratePress（Customizer ＋ Site Header / Navigation Blocks, GenerateBlocks）
- **行モデル**: 旧 Customizer は「Top Bar」「Primary Navigation」を持つ（= Top + Main）。新 Site Header Block はブロックで任意行構成。
- **要素**: Logo, Menu, Button, Search, dynamic content（ブロックで自由）。Top Bar（連絡先・SNS の定番置き場）。
- **骨格プリセット**: GenerateBlocks Pro に **15 Site Header Patterns**。layout は nav-right / nav-left / centered nav / 「ロゴ左・nav 中央・右アイコン」など → `classic`/`nav-right`/`centered`/`centered-stack` をカバー。
- **Top バー**: ● Customizer に Top Bar 標準。
- **Sticky / Transparent**: ● Sticky は Menu Plus（Pro）で fade/slide、transparent header も第一級（Header Element / Site Header Block の sticky 閾値設定あり）。
- 出典: [Sticky Navigation](https://docs.generatepress.com/article/sticky-navigation/), [Transparent Header](https://docs.generatepress.com/article/transparent-header-and-navigation/), [Site Header & Navigation Blocks](https://generatepress.com/mastering-site-header-and-navigation-blocks/), [Header Examples](https://docs.generatepress.com/article/header-examples/)。

### OceanWP（Header Type プリセット）
- **行モデル**: ビルダーではなく**名前付き Header Type の列挙**（最も骨格マップしやすい）。Top Bar は独立セクション。
- **Header Types → 骨格**:
  - `minimal`（1 行・横並び、右上に social）→ **`classic`/`nav-right`**。
  - `top`（最上段メニュー、ロゴ上下）→ **`centered-stack`**（縦積み中央）/ `two-row`。
  - `center`（ロゴ常時中央・左右にメニュー分割、Left Menu / 右メニュー）→ **`centered`**。
  - `full_screen`（ロゴ左・右ハンバーガーのみ）→ **`minimal`**。
  - `medium`（ロゴ＋ナビの中庸レイアウト）→ `classic` 派生。
  - `vertical`（縦サイドヘッダー）→ **bespoke**（本書語彙外。§9「縦置きサイドヘッダー」例外に一致）。
  - `custom`（Elementor 等で自由）→ bespoke。
- **要素**: Logo, Menu, Search, Social, Top Bar（連絡先・SNS）, Cart。
- **Top バー**: ● 独立 Top Bar（連絡先・SNS）。
- **Sticky / Transparent**: ● Transparent Header は無料で第一級（ページ単位）。Sticky は Pro で Shrink / Fixed / 不透明度・Top Bar 同時 sticky。
- 出典: [Customizer Header](https://docs.oceanwp.org/article/901-customizer-header), [Center Header](https://docs.oceanwp.org/article/467-improvement-on-the-center-header-style), [Sticky Header](https://docs.oceanwp.org/article/915-customizer-sticky-header), [center-header.php](https://github.com/oceanwp/oceanwp/blob/master/partials/header/style/center-header.php)。

### Divi（Theme Builder / 既定 menu スタイル）
- **行モデル**: Theme Builder は自由だが、既定テーマには**名前付き menu style**がある。
- **Menu styles → 骨格**: Default（ロゴ左・nav 右）→ `nav-right`/`classic`。Centered（ロゴ中央上・nav 下段）→ `centered-stack`。Centered Inline Logo（ロゴをメニュー中央に挿入）→ `centered`。Slide-in / Fullscreen（ハンバーガー）→ `minimal`。
- **要素**: Logo, Menu, Search, Cart, Button（Theme Builder で自由 module）。Top bar は標準型でなく自作。
- **Top バー**: ○ 既定テーマに第一級 Top Bar なし（Theme Builder で自作）。
- **Sticky / Transparent**: ● Sticky（Sticky at Top, Sticky State）・Transparent fixed header いずれも第一級。
- 出典: [Divi 5 Transparent Fixed Header](https://help.elegantthemes.com/en/articles/14003959), [Centered Inline Logo](https://divisoup.com/centering-the-centered-inline-logo/), [Sticky Header logo switch](https://www.elegantthemes.com/blog/divi-resources/switching-your-logo-on-a-sticky-header-in-divi)。

### Neve（Header/Footer Builder）
- **行モデル**: Top / Main / Bottom の 3 行。各行を 2 / 3 / 5 カラムに分割可。モバイルは別ビルダー＋ Mobile Sidebar。
- **要素**: Logo, Nav, Search（open behaviour）, Button, Cart（WooCommerce）, Social（無制限）, HTML/Widget。→ palette とほぼ一致。
- **骨格マップ**: 既定ロゴ左・nav（→ `classic`/`nav-right`）。Top 行で 2 行系・centered-stack。
- **Top バー**: ● Top 行が第一級。
- **Sticky / Transparent**: ● 行単位で Stick to top / Show only on scroll、Transparent はヘッダー全体。Pro で shrink。
- 出典: [Neve Header Builder](https://docs.themeisle.com/neve/neve-header-builder), [Header Sticky (PRO)](https://docs.themeisle.com/article/1246-header-sticky-pro), [Cart Icon](https://docs.themeisle.com/article/1233-header-cart-icon)。

### Spectra One（ブロックテーマ）
- **行モデル**: ブロック自由。**6 つの Header Pattern**を同梱（Header Replace で差し替え）。
- **要素**: Site Logo, Navigation（justify: left/right/center/space-between, 横/縦）, Button 等のブロック。
- **骨格マップ**: nav の justify で `classic`/`nav-right`/`centered`。6 パターンに左ロゴ・中央ロゴ系。
- **Top バー**: ○ ブロックで構成（専用 Top Bar 概念は薄い）。
- **Sticky / Transparent**: ● sticky・transparent いずれもページ単位で第一級。
- 出典: [Change Header/Footer Patterns](https://wpspectra.com/docs/change-header-footer-patterns/), [Manage Headers](https://wpspectra.com/docs/manage-headers/), [Navigation Block](https://wpspectra.com/docs/navigation-block/)。

---

## 2. 横断: 6 骨格カバレッジ表

各ビルダーがその骨格を「既定/プリセットで提供」（●）か「配置で表現可」（○）か「非対応/bespoke」（−）。

| 骨格 | Kadence | Astra | Blocksy | GeneratePress | OceanWP | Divi | Neve | Spectra | 提供率 |
| --- | :--: | :--: | :--: | :--: | :--: | :--: | :--: | :--: | :--: |
| `classic` | ● | ● | ● | ● | ● | ● | ● | ● | **8/8 (100%)** |
| `nav-right` | ● | ● | ● | ● | ● | ● | ● | ● | **8/8 (100%)** |
| `centered` | ○ | ● | ○ | ● | ● | ● | ○ | ● | **8/8 (≥○)** |
| `centered-stack` | ○ | ● | ○ | ● | ● | ● | ○ | ○ | **8/8 (≥○)** |
| `minimal` | ○ | ○ | ○ | ○ | ● | ● | ○ | ○ | **8/8 (≥○)** |
| `two-row` | ● | ● | ● | ● | ○ | ○ | ● | ○ | **8/8 (≥○)** |

注: 行ビルダー型（Kadence/Astra/Blocksy/Neve/Spectra/GeneratePress-blocks）は全骨格を「配置で表現可」なので原理的に ○ 以上。OceanWP/Divi は**名前付きプリセット**で骨格を直接列挙しており、`minimal`/`centered` を第一級（●）で持つ点が重要。

**結論（骨格頻度）**:
- **業界普遍（必ず第一級で存在）= `classic` と `nav-right`**。全 8 ビルダーが既定で提供。実質これが「最頻ヘッダー」。
- **次点（ほぼ全ビルダーが名前付きで提供）= `centered`（ロゴ中央・左右分割）と `centered-stack`（ロゴ中央上・nav 下段）**。ブランド/マガジン系の定番として OceanWP(center)/Divi(centered, centered-inline)/Astra(Logo Center) が第一級化。
- **`minimal`（ハンバーガーのみ）**: OceanWP `full_screen`・Divi slide-in/fullscreen が第一級。他は表現可。LP/ポートフォリオ需要で常に存在。
- **`two-row`（Bottom フル幅 nav）**: 行ビルダー勢は容易に提供。OceanWP/Divi の単純プリセットには独立名がなく相対的に稀（= 大型サイト向けで頻度低め）。

→ 6 骨格すべてが少なくとも 1 ビルダーで第一級提供されており、**§5 のカタログは過不足ない**ことを裏付け。頻度順位は `classic` ≈ `nav-right` ≫ `centered` ≈ `centered-stack` > `minimal` > `two-row`。

---

## 3. 横断: 要素の標準性表

各要素が「既定/第一級」（●）か「提供あり（Pro/オプション）」（○）か「無し」（−）。

| 要素（§3） | Kadence | Astra | Blocksy | GeneratePress | OceanWP | Divi | Neve | Spectra | 標準度 |
| --- | :--: | :--: | :--: | :--: | :--: | :--: | :--: | :--: | :--: |
| `brand`(Logo) | ● | ● | ● | ● | ● | ● | ● | ● | **8/8** |
| `primaryNav` | ● | ● | ● | ● | ● | ● | ● | ● | **8/8** |
| `menu`(ハンバーガー) | ● | ● | ● | ● | ● | ● | ● | ● | **8/8** |
| `search` | ● | ● | ● | ● | ● | ● | ● | ○ | **8/8 (≥○)** |
| `cta`(Button) | ● | ● | ● | ● | ○ | ○ | ● | ● | **8/8 (≥○)** |
| `social` | ● | ● | ● | ○ | ● | ○ | ● | ○ | **8/8 (≥○)** |
| `infoText`/HTML | ● | ● | ● | ○ | ○ | ○ | ● | ○ | **8/8 (≥○)** |
| `secondaryNav` | ● | ● | ○ | ○ | ○ | ○ | ○ | ○ | 8/8 (≥○) |
| Cart(WooCommerce) | ● | ● | ● | ○ | ● | ○ | ● | ○ | 8/8 (≥○) |
| `langSwitch` | ○ | ○ | ○ | ○ | ○ | ○ | ○ | ○ | Pro/オプション |
| `themeToggle`(light/dark) | − | − | − | − | − | − | − | − | **0/8（WP に無）** |
| `contact`(tel/mail) | (HTML) | (HTML) | (HTML) | (HTML) | ●(TopBar) | (自作) | (HTML) | (HTML) | HTML 経由が標準 |

**結論（要素標準性）**:
- **常時標準（全ビルダー第一級）= Logo / Primary Nav / ハンバーガー / Search**。この 4 つはヘッダーの中核。
- **準標準（ほぼ全ビルダーで提供）= CTA Button / Social / HTML(自由欄＝infoText/contact) / Secondary Nav / Cart**。
- **`contact`（電話/メール）は専用要素より「HTML/自由欄」で表現するのが業界標準**。OceanWP のみ Top Bar の連絡先として準第一級。→ 本書の `contact` を構造化フィールドとして持つのは差別化になるが、最低限 `infoText`(サニタイズ HTML) があれば業界水準を満たす。
- **`themeToggle`（light/dark 切替）は WP ビルダーに存在しない** = NeNe 独自要素。既定 ON は妥当だが「業界標準だから」ではなく自社プロダクト方針として持つもの（OFF 可は正しい）。
- **`langSwitch` は全ビルダーで Pro/オプション** = 既定 OFF（本書どおり）が妥当。

---

## 4. 横断: モディファイア普及率（topbar / sticky / transparent）

| モディファイア | Kadence | Astra | Blocksy | GeneratePress | OceanWP | Divi | Neve | Spectra | 普及 |
| --- | :--: | :--: | :--: | :--: | :--: | :--: | :--: | :--: | :--: |
| **Top バー**（連絡先/告知/SNS） | ● | ● | ● | ● | ● | ○ | ● | ○ | **8/8（6 が第一級）** |
| **Sticky**（追従） | ●/○ | ○(Pro) | ● | ●(Pro) | ●(Pro) | ● | ● | ● | **8/8** |
| **Sticky shrink**（縮小追従） | ○ | ○ | ○ | ○ | ●(Shrink) | ○ | ○(Pro) | ○ | 普遍だが多くは Pro |
| **Transparent / overlay** | ○ | ○(Pro) | ● | ●(Pro) | ●(無料) | ● | ● | ● | **8/8** |

**結論（モディファイア）**:
- **Top バー = 8/8 で提供、うち 6 が第一級**。連絡先/営業時間/告知/SNS の置き場として業界標準。→ 本書 §2 の Top 行を第一級に置く設計は正解。
- **Sticky = 8/8 で対応**（多くは Pro 機能）。**Transparent/overlay = 8/8**。両者はヘッダー機能の事実上の必須セット。
- **Sticky shrink（縮小追従）は普遍だが Pro 寄り** → NeNe の `headerSticky=shrink` は「あると差別化、無くても許容」の優先度。まず `sticky`（単純追従）、次に `shrink`。
- Top バーのモバイル既定非表示（本書 §6）も業界一般（多くが TopBar をモバイルで畳む）と整合。

---

## 5. NeNe への具体的推奨

### 5.1 デフォルト提供骨格セット（優先順位）
1. **`classic`（既定）** と **`nav-right`** — 8/8 が第一級。最優先で base CSS を磨き込む。現行 `PublicSiteShell` の `classic` 既定は業界最頻と一致。
2. **`centered` と `centered-stack`** — ブランド/マガジン需要の定番。Astra/OceanWP/Divi が名前付きで第一級化しており、NeNe でも「ブランドサイト」プリセットとして第二優先で実装。`centered-stack` は 2 行系なのでモバイル集約コスト（§9）を見込む。
3. **`minimal`** — LP/ポートフォリオ向け。ドロワー専用なので実装コスト低。第三優先だが ROI 高い（既存ハンバーガー実装を流用）。
4. **`two-row`** — 大型/官公庁向けで頻度は最低。Bottom 行フル幅 nav の実装が重く、需要も限定的 → **後回し可**。MVP では `two-row` を落として 5 骨格でも業界カバレッジを実質維持できる。

→ **MVP 推奨セット: `classic` / `nav-right` / `centered` / `minimal`（＋ `centered-stack`）。`two-row` は Phase C 以降。**

### 5.2 要素の既定（業界整合）
- 第一級で必須実装: **Logo / Primary Nav / ハンバーガー / Search**（全ビルダー標準）。本書の既定 ON と一致。
- `contact` は専用構造体として持ちつつ、**`infoText`（サニタイズ HTML 自由欄）を確実に用意**するのが業界水準（HTML 要素が連絡先/告知/営業時間の共通解）。XSS サニタイズ契約（§9）を必須化。
- `themeToggle` は NeNe 独自。既定 ON＋OFF 可は維持（業界には無い差別化要素）。
- `langSwitch` は既定 OFF（全ビルダーで Pro/オプション扱い）で正しい。

### 5.3 モディファイア優先順位
1. **Top バー（topbar）** — 8/8 標準。`contact`/`infoText`/`social` の置き場として Phase A で実装する方針（本書 §10）は業界整合的に最重要。
2. **Sticky（単純追従, `sticky`）** — 8/8 対応。実装コスト低（`position: sticky` + JS で `data-*`）。Phase C 先頭。
3. **Transparent/overlay（`surface=overlay`）** — 8/8 対応。hero 重ね需要が大きい。Sticky と同 Phase で。
4. **Sticky shrink（`shrink`）** — 普遍だが多くは Pro。最後でよい。

### 5.4 語彙の妥当性（裏付けまとめ）
- 6 骨格カタログ（§5）は**過不足なし**。各骨格が最低 1 ビルダーで第一級提供される。
- 3 行 × 3 ゾーン（§2）は **Kadence/Astra/Blocksy/Neve が完全一致**で採用しており、業界収束モデルとして妥当。
- bespoke escape hatch（§9）の必要性も裏付け: OceanWP `vertical`（縦サイドヘッダー）/ `custom` は本書語彙外で、これらは bespoke 行き。

---

## 6. 調査メモ / 限界

- **WebFetch は本環境で権限拒否**。全データは WebSearch の公式 docs サマリ＋ GitHub ソース（OceanWP `center-header.php`）に基づく。各ビルダーの「第一級 vs Pro/オプション」区別は docs 記述から推定であり、バージョン差で前後しうる。
- **動的 JS デモ（スターターテンプレ実物）は構造復元が不安定**なため、頻度は「ビルダーが既定/プリセットで提供する骨格」を代理指標とした（§11 の方針どおり）。実テンプレ 30–50 サンプリングの実測は未実施（必要なら WordPress.org パターンディレクトリ巡回で補完可能）。
- 取得できなかったビルダー: なし（目標 8 種すべて docs から取得）。ただし Spectra/GeneratePress はブロックテーマ型で「行」概念が緩く、骨格マップは nav justify とパターン名から推定。

## 出典一覧
- Kadence: <https://www.kadencewp.com/help-center/docs/kadence-theme/how-to-customize-the-kadence-header/>, <https://www.kadencewp.com/help-center/docs/kadence-theme/editing-a-row-in-the-header/>, <https://www.kadencewp.com/help-center/docs/kadence-theme/kadence-classic-header-items/>
- Astra: <https://wpastra.com/docs/header-builder-options/>, <https://wpastra.com/docs/elements-in-header-footer-builder/>, <https://wpastra.com/docs/above-header-section/>, <https://wpastra.com/docs/create-header-with-centrally-positioned-logo/>
- Blocksy: <https://creativethemes.com/blocksy/docs/header-elements/header-builder-elements/>, <https://creativethemes.com/blocksy/docs/header-builder/sticky-header/>, <https://creativethemes.com/blocksy/docs/header-elements/html/>
- GeneratePress: <https://docs.generatepress.com/article/sticky-navigation/>, <https://docs.generatepress.com/article/transparent-header-and-navigation/>, <https://generatepress.com/mastering-site-header-and-navigation-blocks/>, <https://docs.generatepress.com/article/header-examples/>
- OceanWP: <https://docs.oceanwp.org/article/901-customizer-header>, <https://docs.oceanwp.org/article/467-improvement-on-the-center-header-style>, <https://docs.oceanwp.org/article/915-customizer-sticky-header>, <https://github.com/oceanwp/oceanwp/blob/master/partials/header/style/center-header.php>
- Divi: <https://help.elegantthemes.com/en/articles/14003959>, <https://divisoup.com/centering-the-centered-inline-logo/>, <https://www.elegantthemes.com/blog/divi-resources/switching-your-logo-on-a-sticky-header-in-divi>
- Neve: <https://docs.themeisle.com/neve/neve-header-builder>, <https://docs.themeisle.com/article/1246-header-sticky-pro>, <https://docs.themeisle.com/article/1233-header-cart-icon>
- Spectra: <https://wpspectra.com/docs/change-header-footer-patterns/>, <https://wpspectra.com/docs/manage-headers/>, <https://wpspectra.com/docs/navigation-block/>
</content>
</invoke>
