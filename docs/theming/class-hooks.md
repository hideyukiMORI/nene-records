# 公開サイト クラスフック一覧（L2 components.css 用）

> テーマの `components.css` が **狙ってよい既存クラス**のカタログ。これらを再スタイルすれば、**DOM を変えずに**見た目の印象を大きく変えられる（L2）。新規 DOM は作らない。
>
> - すべて `.nene-public` 配下。`components.css` のセレクタは必ず `.nene-public[data-theme='<id>'|'<id>-dark']`（または `.nene-public[data-theme^='<id>']` で light/dark 一括）でスコープする。
> - 値の正本は [`public-theme-contract.md`](./public-theme-contract.md)。完全な現行スタイルは reference の `public-site.css`。

## スコープの書き方

```css
/* light/dark 一括（.nene-public を含むのでバリデータ通過） */
.nene-public[data-theme^='myid'] .card { … }
/* トークン上書きもスコープ内で可（例: 角を立てる） */
.nene-public[data-theme^='myid'] { --radius-md: 2px; }
```

## クラス一覧（領域別）

### 共通シェル / chrome

`.hd` `.hd__in` `.hd__nav` `.hd__search` `.hd__menu` `.navlink` `.brand` `.brand__mark` `.brand__name` `.brand__tag` `.iconbtn` `.themesw` `.themesw__btn`
フッター: `.ft` `.ft__grid` `.ft__brand` `.ft__col` `.ft__free` `.ft__bar`
モバイル: `.drawer` `.drawer__panel` `.drawer__head` `.drawer__scrim`

### ヒーロー（トップ）

`.hero` `.hero__grid` `.hero__copy` `.hero__kicker` `.hero__title` `.hero__lead` `.hero__cta` `.hero__meta` `.hero__stat` `.hero__art` `.hero__art-frame` `.hero__art-tag`

### 最新フィード / カード

`.featured` `.featured__media` `.featured__body` `.featured__title` `.featured__excerpt` `.featured__metarow` `.featured__foot`
`.cardgrid` `.card` `.card__media` `.card__title` `.card__excerpt` `.card__metarow`
プレースホルダ画像: `.eyecatch`

### エンティティタイプ導線

`.types` `.typecard` `.typecard__main` `.typecard__name` `.typecard__slug` `.typecard__count` `.typecard__arrow`

### セクション見出し

`.section` `.section__head` `.section__title` `.section__link` `.eyebrow` `.tbadge` `.meta` `.mono`

### 一覧 / アーカイブ / 検索

`.pagehead` `.pagehead__row` `.pagehead__title` `.pagehead__sub`
リスト: `.rowlist` `.row` `.row--compact` `.row__media` `.row__title` `.row__excerpt` `.row__metarow` `.row__body` `.row__foot`
検索/フィルタ: `.searchbar` `.searchhint` `.filterchips` `.chip` `.chip__n` `.viewtoggle` `.tag` `.tags`
グルーピング: `.resultgroup` `.resultgroup__title`
ページャ: `.pager`

### 記事詳細 / 本文

`.article` `.article__head` `.article__metarow` `.article__title` `.article__byline` `.article__avatar` `.article__hero` `.article__tags` `.backlink`
本文（Markdown）: `.prose` / `.markdown-body`（`h2/h3/p/ul/ol/li/blockquote/a/code` 等の子要素）
関連: `.related`
コメント: `.comments` `.comments__title` `.comment` `.comment__head` `.comment__author` `.comment__avatar` `.comment__date` `.comment__body` `.cform` `.cform__row`

### サイドバー・ウィジェット

`.aside` `.widget` `.widget__title` `.search` `.poplist` `.poplist__rank` `.poplist__t` `.poplist__m` `.cal` `.today`

### 部品 / 状態 / レイアウト

`.btn` `.btn--primary` `.btn--ghost` `.input` `.textarea` `.empty` `.empty__icon` `.empty__title` `.empty__text`
レイアウト: `.layout` `.layout__main` `.is-2col` `.is-3col` `.wrap`
状態: `.is-current` `.is-open` `.is-disabled`

## L2 のコツ（印象を変える）

- **角・罫線・影**: `--radius-*` の上書き＋ボーダー/影で “柔らかい↔硬派” を切替。
- **メディア**: `.card__media` / `.featured__media` / `.article__hero` に枠・`filter`（duotone/グレースケール）・aspect 変更。
- **タイポの表情**: 見出しの `text-transform` / `letter-spacing`、`.eyebrow` `.meta` を `--font-mono` 等へ。
- **リズム**: `--space-*` 上書きで密度を変える。`.section__head` に罫線。
- **グリッド**: `.cardgrid` / `.types` の `grid-template-columns` を変えて版面の密度・列数を変える（DOM はそのまま）。
- **幅でメリハリ（重要）**: `--content-w`（`.wrap` のページ全体幅）/ `--measure`（本文幅）/ `--gutter`（左右余白）/ `.layout.is-2col` のサイド列幅をテーマごとに変える。全テーマ同じ幅だと印象が似るので、狭め＋広い余白＝親密、広め＝情報密度、で空気感を変える。
- 禁止: 外部 `url()` / `@import` / 任意 JS / 新規 DOM 前提のスタイル。
