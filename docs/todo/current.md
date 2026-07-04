# Current Work

Last updated: 2026-07-05

## 直近: 共有ホスティング Tier A インストーラ 公開（S3・#707・2026-07-04〜05）

- **#707 Tier A インストーラ: 完了・公開済み（GitHub Release `v0.5.0`）** —
  NENE2 `Nene2\Install` toolkit（v1.6.0・Packagist）を配線した `public_html/install/index.php`
  （要件チェック→DB/tenant→migrate→管理者→完了・再訪403）＋ `tools/build-release.sh`
  （Packagist `^1.6` 実体 vendor・**assets/theme-thumbnails を public_html へ移設**＝共有ホスティングに
  Apache Alias が無いため）。関所全5スライス合格→PR #708 マージ→施主 GO で
  **`nene-records-0.5.0.zip`（66MB）＋.sha256 を Release 公開**（Latest・target=main）。
  版数は既存 v0.4.0 の次＝0.x 継続（1.0 は update 機構＋磨き込み後）。**Origin 独立で配布可**
  （v1 は自己完結 zip・インストーラは外部 DL しない）。設置手順は `docs/install-tier-a.md`。
- 併せて直した設置系の本質ギャップ: ① **共有ホスティング env ブリッジ**（phpdotenv v5 は
  putenv しない→ getenv 直読み設定が .env-only 環境で全て既定値化。front controller で写す）
  ② **APP_BASE_PATH の入口 prefix strip**（base-path 節の「残 S3」を消化）
  ③ **#713 再設置ガード素通し**（`.env` 無し早期 return が Docker 本番でガードを無効化していたのを probe 常時実行に）。
- 残（フォローアップ）: #709 フォント on-demand（font-less 11MB）／#710 ウィザードのサイト名を
  site_name 設定へ反映／NENE2 #1482 参照レンダラ入力型／Suite catalog 登録＋Origin 差替（board C）。
  設計トレードオフ（記録）: migration 失敗時も .env は残す（phinx が ConfigLoader 経由で .env を読むため
  書込先行が構造的に必須。docroot 外・再送信で上書き・ガードは users 基準で実害なし）。

## 直近: 設定・テーマ・本番反映のバグ修正（2026-07-04）

- **#705/#706 テーマサムネ 404**: 本番 Apache が `/assets` しか配信せず `/theme-thumbnails/*` が
  全 404（テーマ選択ページの画像切れ）。prod/dev Dockerfile に Alias 追加。**本番反映済み**。
- **#711/#712 設定定義シード欠落（systemic）**: org 作成時に `setting_defs` が seed されず、
  signup/インストーラ産 org は設定 UI がほぼ空（site_name/logo が出ない・active_theme 無しで
  テーマ保存も不発）だった。`PdoDefaultSettingDefsSeeder`（17定義・org 作成時）＋バックフィル
  migration `20260715000000`。**本番反映済み**（全 org が 17 定義に回復）。
- 上記＋#701/#703 の main 差分を **本番（nene-records.com）へデプロイ済み**。🔴 デプロイ知見:
  toolkit 依存が進んだらサーバーの sibling `NENE2/` も rsync してから build（v1.6.0 未同期だと
  baked vendor に `Nene2\Install` が欠けて installer が Fatal になる — 実際に踏んだ）。

## 直近: WP式「固定ページをトップに」＋セッション文書の保全（2026-07-02〜04）

- **#701 front_page 設定: 完了（PR #702 マージ・2026-07-04）** — 任意の公開レコード1件を
  org 設定 `front_page` にピン留めし、公開トップ `/` で SSR（canonical=ルート・og:type=website・
  JSON-LD WebPage）。元 permalink は **302**（クエリ維持）で `/` へ、sitemap は個別 URL を `/` に置換。
  管理UI「ホームページの表示」（SiteSettingsPage 先頭・i18n 6ロケール）と SPA 側の描画/`/`への再ホームも同 PR。
  multi-agent レビュー指摘 §3-1〜3-7 対応済み（正本: `docs/handoff-2026-07-03-front-page-review.md`・
  設計: `docs/handoff-2026-07-03-front-page.md`）。front_page ルールは `FrontPageSetting`
  （`resolvePublished()` / `assertPinnable()`）に一元化。
  実装知見: NENE2 の `/` ルートは登録順で勝つため、上書きは `SingleOriginKernel` の**エッジ層**で行う。
- **セッション文書の保全（#703）** — 日報3本（06-26/06-27/07-03）・Qiita 記事草稿・base-path
  スモークスクリプトを main へ収録。**handoff-2026-06-25 / 06-27 は運用機密（本番 IP・SSH/DB 手順）を
  含むため公開リポには置かず、private `nene-records-marketing` の `ops-inbox/records/` へ移設**
  （本書中の `docs/handoff-2026-06-25.md` §A 参照はそちらを指す）。
- **本番未反映**: #663 以降〜#702 の main は本番未デプロイ（board タスク・実施は施主 GO 待ち）。

## 短期TODO（directory-view レビュー由来・2026-06-27 / milestone「ページ階層/ディレクトリ 仕上げ」#1 — 完了）

> 議事: `docs/review/directory-view-personas-2026-06-27.md`。**マイルストーン #1 完了**（P0＋全P1＋目玉P2 着地）。
> - [x] **#656 P0**: 公開 custom-permalink ページを実SPAで解決・描画（#663・実機確認済）
> - [x] **#657 P1**: ディレクトリ表示拡充＋ titleless fallback humanize 統一（#664）
> - [x] **#658 P1**: 「＋ここに新規」permalink 接頭辞補完（#665。手動並び順は #659 へ統合）
> - [x] **#659 P2**: DnD 親移動＋自動301／ツリー内検索＋ハイライト／手動並び順 ▲▼＋menu_order（#666–#670）
> - [x] **#660 P2**: 磨き込み — パス視認性・フォルダ開閉記憶・a11y フォーカス・sitemap（custom-permalink 掲載）確認済
> - 残: **#671** phantom セグメントのセクションindex 自動生成（機能規模のため #660 から分離）。全 PR は `main` 反映のみ＝**本番デプロイ未**（本番に custom-permalink ページが無く非緊急）。

## 状態サマリー

**M1 — Usable Blog CMS: 完了（2026-05-24）**

**M2 — Team-Ready CMS: 完了**

**M3 — Headless CMS Platform: 完了（2026-05-26）**

**M4 — 機能拡充＋使い勝手改善: 完了（2026-05-27）**

**M5 — ユーザー管理・メディアライブラリ: 完了（2026-05-27）**

**M6 — コメント機能: 完了（2026-05-27）**

**M7 — マルチテナント基盤・スーパー管理画面: 完了（2026-05-27）**

**M8 — マルチテナント運用機能: 完了（2026-05-27）**

**M9 — マルチテナント完成（1ユーザー=1組織）: 完了（2026-07-01）**

**M10 — マルチテナント包括テストスイート + E2E 拡充: 完了（2026-05-27）**

**M11 — 通知・運用機能の拡充: 完了（2026-05-28）**

**M12 — メディアライブラリ強化（ストレージ抽象化・派生画像）: 完了（2026-06-12）**

**M13 — 管理コンソール再設計: 完了（2026-06-12）**

**M14 — 公開レイアウト & Custom Page: 完了（2026-06-12）**

**M15 — ウィジェット（Epic #324・全フェーズ）: 完了（2026-06-13）**

**M16 — 公開サイトのテーマシステム（Epic #367・コア）: 完了（2026-06-20）**

**ポストM16 — リッチページ／ブロック・型チェック是正・i18n 6言語化: 完了（2026-06-22〜24）**
（#486 型付き投稿ブロック / #489 型チェック実稼働化（tsc 1197→0）/ #390 ハイブリッドテーマ engine /
#530 管理画面6言語化。詳細は下記「ポストM16」セクション。リッチページ #491 / Custom Page 厳格契約 #311 /
フロント全体監査 #507 は一部進行中。）

**ポスト #536 — UX/SEO/SSR・WordPress 移行・本番配備・subdomain SaaS 本番稼働: 進行中（コア出荷済み）（2026-06-24〜26）**
（WordPress 比較×6ペルソナ討論を起点に公開 SEO/SSR を最優先化。公開 SEO/SSR #537 / OG 画像 #547 /
計測 GA4+Consent #583/#584 / sitemap・robots #585–#588 / WYSIWYG #538 / 公開 i18n #540 /
WordPress 移行 WXR #539 / 本番配備＋ subdomain マルチテナント SaaS を達成し
**https://nene-records.com で本番稼働**（apex=プロモ LP・`slug.nene-records.com` で即利用・自動 TLS・メール認証）。
詳細は下記「ポスト #536」セクション ＋ 引き継ぎ書 `docs/handoff-2026-06-25.md` §A。
残: P1 #541 設定 UI 化 / #543 権限+2FA ＋ 公開前の signup レート制限・soak・収益モデル。）

最新の検証基準（2026-06-26 実測）: PHPUnit 1038 tests / 2887 assertions / PHPStan level 8（1250 files・エラー 0）/
CS-Fixer / OpenAPI / MCP（69 tools）＋ Playwright E2E 220 tests（20 files）＋
`npm run check`（type-check / lint / prettier / test 418（84 files）/ validate:themes / knip / storybook）全グリーン。

---

## 進行中エピック（現在のフロンティア）

> 直近の作業文脈は引き継ぎ書が正本:
> `docs/handoff-2026-06-25.md` §A（**最新**・#536 UX/SEO/SSR・本番配備・subdomain SaaS）/
> `docs/todo/handoff-2026-06-23-rich-pages-blocks.md`（リッチページ／ブロック）/
> `docs/todo/handoff-2026-06-17-public-theme-system.md`（テーマシステム）/
> `docs/todo/handoff-2026-06-19-mcp-connector.md`（MCP コネクタ）。

現在の主戦線は **EPIC #536（UX/SEO/SSR・WordPress 移行・本番配備・subdomain SaaS）**。
公開 SEO/SSR・計測・WordPress 移行・本番 SaaS 化のコアは出荷済み（**https://nene-records.com 本番稼働**）で、
残るは P1 の **設定 UI 化 #541 / 権限・2FA #543** と、公開拡大前提の **signup レート制限・soak・収益モデル**（engineering 外が中心）。
並行して継続する旧フロンティア＝**リッチページ化／Custom Page #491 / #311**、**フロント全体監査 #507**、
**公開テーマ派生 #362 / #371 / #372 / #373**。テーマのコア（runtime テーマ・カスタマイザ・スタイルフラグエンジン・MCP bridge）は
M16＋#390 で完了済み。#536 の内訳は下記「ポスト #536」セクション。

| Issue | 内容 | 状態 |
| --- | --- | --- |
| #536 | epic(ux): 公開 SEO/SSR 最優先の UX 改善（WordPress 移行・本番配備・subdomain SaaS） | 進行中（コア出荷済み・本番稼働。残 P1: #541/#543 ＋ 公開前の signup レート制限/soak/収益） |
| #539 | WordPress 移行（WXR インポート＋メディア取込＋301 マップ） | 実装済み（#561–#572 マージ・epic は #536 P1 として open） |
| #541 | 設定の生 JSON 露出を構造化 UI へ | 未着手（#536 P1） |
| #543 | 権限細分化＋2FA＋パスワード忘れフォーム＋scoped API トークン | 未着手（#536 P1） |
| #491 | リッチページ化 epic（ブロックを本文の主役に／レイアウトブロック／bundle 完全カスタム） | 進行中（WS1–WS3 S3a–S3d 済・残: S3e=ClaudeDesign 連携＋scoped creds＋JSON-LD） |
| #311 | Custom Page（sandboxed iframe + 二重表現SEO + ClaudeDesign 連携）厳格契約 | 進行中（S3a–S3d 済・S3e 残。#491 と一体） |
| #507 | フロントエンド全体監査の remediation（75 finding / 18 WS + セキュリティ + lint 強化） | 進行中（#508–#531 マージ済み・残: WS-04/05/14/15/18・ENT-7 等＝要 API/プロダクト判断） |
| #372 | テーマ カスタマイザ（トークン上書きノブ）Phase 2 | 進行中（残: 画像ノブ logo/hero・メディア #299 連携。ライブプレビュー iframe は #538 で達成済み） |
| #371 | モーション/インタラクション能力レイヤ Phase 1.5 | 進行中（基盤＋scroll-reveal #477・header sticky-shrink #479・hero split/gradient #481 済。残: View Transitions 等） |
| #373 | ClaudeDesign「MD→テーマ」量産パイプライン＋バリデータ Phase 3 | 未着手 |
| #362 | 公開サイトのテーマ/見た目の作り込み（design-first / ダークモード対応）epic | 進行中（上記テーマ系を束ねる） |
| #586 | `/machine/health` に installed version を載せ Suite 更新追跡に対応 | 未着手（運用・standalone） |

> 完了化されたフロンティア: **#537**（公開 SEO/SSR・**クローズ**）/ **#538**（WYSIWYG ライブプレビュー・**クローズ**）/
> **#540**（公開 i18n・**クローズ**）/ **#542**（relation スキーマ UI・**クローズ**）/ **#390**（ハイブリッドテーマ engine・クローズ）/
> **#402**（feedColumns・クローズ）/ **#435**（ClaudeDesign 向け MCP 実践ガイド・クローズ）。

### 完了済みエピック（旧「進行中」）

- **#347 名前付きメニュー移行（location → menus）: 完了** — PR1 #348 / PR2a #349 / region 一本化 #350・#351。
  `navigation_items.location` は migration（`20260614000000_drop_location_from_navigation_items`）で撤去済み・`NavLocations` 参照ゼロ。
  項目は `menu_id` で名前付きメニューに所属、ヘッダー/フッターは region ウィジェットで描画。
  `menus.location`（テーマ表示場所）は方針どおり存置。
- **#352 外観 › レイアウトビルダー: 完了** — スライス1〜5（#353〜#358）＋ページ別 cfg・公開接続（#360/#361/#408）まで全マージ。
  `layout_config` 設定（`20260617000001_add_layout_config_setting`）で永続化済み。

---

## ポスト #536 — UX/SEO/SSR・WordPress 移行・本番配備・subdomain SaaS（2026-06-24〜26）

ポストM16 後、WordPress 比較×6ペルソナ討論（#534/#545）を起点に公開 SEO/SSR を最優先に据えた EPIC #536 を集中実装。
**最大の残壁だった「非技術者の入口」を subdomain 型マルチテナント SaaS の本番稼働（https://nene-records.com）まで到達させた。**
正本は引き継ぎ書 `docs/handoff-2026-06-25.md` §A ＋ 日報 `docs/daily/2026-06-26.md` ＋ 議事録 `docs/review/`。

### 公開 SEO/SSR 統合（#537・P0・完了）

| Issue | PR | 状態 | Summary |
| --- | --- | --- | --- |
| #537 | #544 / #548 / #549 / #553 / #554 | 完了 | SSR head に OGP/Twitter/canonical/JSON-LD（#544）、OG 画像を派生エンジンで自動生成し og:image 充填（#547/#548）、実 permalink でクローラブル SSR（#549 S2）、単一オリジンで本番 SPA をマウント（#553 S3）、`/view`→canonical 301＋SPA シェルフォールバック（#554 S4）。方式＝**PHP 前段＋SPA シェル**（Node SSR / bot-prerender は不採用）。 |
| #557 | #558 | 完了 | 公開 HTML レスポンスの CSP を SPA 対応へ緩和（単一オリジン・厳格性維持）。 |

### WordPress 移行 — WXR インポート（#539・P1・実装済み）

| Issue | PR | 状態 | Summary |
| --- | --- | --- | --- |
| #539 | #561–#566 | 実装済み | WXR パーサ＋インポート計画プレビュー（#561 S1）、実行インポート（#562 S2・エンティティ/タグ生成・冪等）、HTTP エンドポイント（#563）、管理画面 UI（#564）、301 リダイレクトマップで SEO 資産保全（#565 S3）、メディア添付取込＋本文画像 URL 差替（#566 S4）。 |
| #539 | #567–#572 | 実装済み | メディア取得の SSRF 遮断（#567）、単一オリジン fallback を PSR-15 `SingleOriginKernel` に集約（#568）、WXR 応答を OpenAPI 名前付きスキーマで型付け（#569）、SSR で html 型をサーバ側サニタイズ（#570）、本文を必ず html 型へ格納し忠実描画（#571）、Yoast/RankMath/AIOSEO の SEO メタ取込（#572）。 |

> #539 は全スライス（#561–#572）マージ済みだが epic issue 自体は #536 P1 として open のまま。

### 計測（GA4/GTM＋Consent Mode v2）

| Issue | PR | 状態 | Summary |
| --- | --- | --- | --- |
| #536 | #583 / #584 / #589 | 完了 | バックエンド核（ID 厳格検証・**nonce 付き** head・consent default をローダ前・CSP は ID 設定時のみ緩和・#583）、フロント（SPA ルート page_view 初回スキップ＋同意バナー・#584）、同意既定のドロップダウン化（#589）。既定は analytics OFF（厳格 CSP）、管理者が実 ID を設定した時のみ有効。 |

### SEO 周辺（sitemap / robots）

| Issue | PR | 状態 | Summary |
| --- | --- | --- | --- |
| #536 | #585 / #587 / #588 | 完了 | `sitemap.xml`（org スコープ・正規 permalink・lastmod・5 万 URL 上限・#585）、`robots.txt`（バックオフィス Disallow・絶対 Sitemap 参照・#587）、大規模時のインデックス分割（既定 45,000 超で sitemapindex へ・#588）。 |

### WYSIWYG ライブプレビュー（#538・P0・完了）

| Issue | PR | 状態 | Summary |
| --- | --- | --- | --- |
| #538 | #590 / #591 | 完了 | ブロック編集のライブプレビュー（公開と同一 `BlocksRenderer`・`.nene-public` スコープ・#590 ①）、テーマカスタマイザの iframe ライブプレビュー（postMessage・同一オリジン origin 検証・#591 ②）。#372 の「ライブプレビュー iframe」も充足。 |

### 公開サイト多言語化（#540・P1・完了）

| Issue | PR | 状態 | Summary |
| --- | --- | --- | --- |
| #540 | #592 / #593 | 完了 | 公開コンテンツのロケール交渉＋`<html lang>`/canonical/hreflang（全 6＋x-default・#592 S1）、公開ヘッダの言語スイッチャ＋一覧（browse/recent/search/tag/archive/relation）のロケール解決（#593 S2）。bootstrap サーバ側解決でハイドレーション一貫。 |

### relation フィールド スキーマ UI（#542・P1・完了）

| Issue | PR | 状態 | Summary |
| --- | --- | --- | --- |
| #542 | #550 | 完了 | relation 型フィールドのスキーマ UI を配線。 |

### 本番配備・オンボーディング

| Issue | PR | 状態 | Summary |
| --- | --- | --- | --- |
| #536 | #573 / #574 / #575 / #579 / #580 | 完了 | ワンコマンド初回導入（組織+admin・冪等・#573）、本番スタック＋**fresh-DB migration P0 修正**（#574）、多段 `Dockerfile.prod` で self-contained イメージ（#575）、本番運用 3 点（healthcheck/backup/logs・#579）、README 実態整合（NENE2 sibling-clone 明記・#580）。 |
| #536 | #577 / #578 / #582 | 完了（本番限定バグ） | デプロイで顕在化した 3 件: org 未解決パスで `UrlRedirectResolver` が fatal（#577）、本番 compose の `MAIL_DSN` 空既定で全リクエスト 500（#578・null transport へ）、単一 org の org-not-resolved（空 seed×ORG_SLUG・`??`→`?:`・#582）。 |

### サブディレクトリ設置（base path 基盤・zip インストーラ前提）

| Issue | PR | 状態 | Summary |
| --- | --- | --- | --- |
| #536 | #595 / #596 / #597 / #599 | 進行中 | バックエンド URL 生成の base 前置（`APP_BASE_PATH`・S1 #595）、フロント・ランタイム base（`<base href>` 由来・S2 #596）、CSP ブロック解消（#597）、ディレクトリ方式マルチテナントの公開 SEO 配線（S-path #599）。**残 S3**: Web サーバの prefix strip ＋ zip パッケージング。 |

### subdomain マルチテナント SaaS（本番 cutover・完了）

| Issue | PR | 状態 | Summary |
| --- | --- | --- | --- |
| #536 | #600–#609 | 完了（本番稼働） | on-demand TLS の ask エンドポイント（#600 ①）、apex をグローバル面として通す（#601 ②）、公開セルフサーブ signup（BE #602 / FE #603 ④）、apex デフォルト・プロモ LP（#604）、cron を全テナント対応（#605 ③ blocker）、`/`+html→SPA シェル（#606）、メール env キー是正 MAIL_FROM_ADDRESS（#607）、メール認証ソフトゲート（#608）、確認後にテナントログインへ案内（#609）。**本番＝https://nene-records.com（apex=LP / `slug.nene-records.com` で即利用 / 自動 TLS / Resend メール）、`records.nene-suite.com`→301。** |

### ペルソナ討論・検証レポート（`docs/review/`）

| Issue | PR | Summary |
| --- | --- | --- |
| #534/#545/#551/#555/#559 | #535/#546/#552/#556/#560 | WordPress 比較×6ペルソナ UX 検証（#534/#535）、12 ペルソナ討論（#545/#546）、第 2 回（実装後・#552）、本番デプロイ実機検証（#555/#556）、第 3 回（デプロイ実証後・#560）。 |
| #536 | #576/#581/#594/#598/#610 | 第 4 回（#576）、第 5 回（#581）、計測/UTM 補遺（#594）、テナント URL 方式討論（#598）、第 6 回（#610）。判定は「使えるか」→「商売・信頼に足るか」へ質的転換（見送り 5→2・今すぐ 2→4）。 |

**残課題（ペルソナ第 6 回の処方順）:** ① signup レート制限（最優先・小・公開前提＝大量 org 作成の濫用対策）/ ② soak（実コンテンツ投入＋数週間の無事故運用）/ ③ 収益モデルの事業判断（有料プラン/独自ドメイン SKU/保守 SLA・`CustomDomainResolutionStrategy` の土台あり）。詳細は引き継ぎ書 `docs/handoff-2026-06-25.md` §A。

---

## ポストM16 — リッチページ／ブロック・型チェック是正・i18n（2026-06-22〜24）

M16 後に main へマージした作業。正本は `docs/todo/handoff-2026-06-23-rich-pages-blocks.md`。

| Issue | PR | 状態 | Summary |
| --- | --- | --- | --- |
| #486 | #487 / #488 | 完了 | 型付き投稿ブロック EPIC。本文ブロック5種 `text/callout/hero/gallery/chart` ＋ ホーム hero。第一者描画（任意 JS 無し）/ サーバ検証（信頼境界）/ sr-only SEO 投影 / URL allowlist。 |
| #489 | #490 | 完了 | フロント型チェック是正。root tsconfig が src を1ファイルも検査していなかったのを `tsc -p tsconfig.app.json --noEmit` に修正し、隠れていた tsc エラー **1197→0**。以後 CI が型を実検査。 |
| #491 | #492–#499 | 進行中 | リッチページ化 EPIC。WS1=コンテンツタイプ スターター（blank/article/rich_page/custom_page）、WS2=レイアウトブロック `group/columns/spacer/divider`（深さ2・leaf-only）、WS3=dual-SEO bundle（`{html,seoText}`・seoText 必須）/ full-bleed custom レイアウト / `text_fields.value` を LONGTEXT 化。**残: S3e**（ClaudeDesign 連携＋scoped creds＋JSON-LD）。 |
| #311 | #496–#499 | 進行中 | Custom Page 厳格契約（#491 WS3 と一体）。S3a–S3d 済・S3e 残。 |
| #501 | #502–#514 | 完了 | ブロック関連フロントの構造リファクタ（規約整合・重複排除・god 分割・UI 共通化）。 |
| #390 | #394 / #401 | 完了 | テーマを「JSON プリセット＋汎用エンジン（スタイルフラグ）」へ — ハイブリッド。全6フラグ base CSS・カスタマイザ UI・validator 実装済み（EPIC クローズ）。 |
| #507 | #508–#531 | 進行中 | フロントエンド全体監査の remediation（18 WS）。i18n（6言語化 #530/#531 含む）/ フォーム a11y（useId）/ 状態色のセマンティックトークン化 / formatter 集約 / theme query-key factory 化 / vendor 分割（620→209kB）等。残: WS-04/05/14/15/18・ENT-7（要 API/プロダクト/アーキ判断）。 |
| #530 | #531 | 完了 | 管理画面を6言語（en/ja/de/fr/pt-BR/zh-Hans）全980キーで完備。`locales.test` を5ロケール完全性＋プレースホルダ整合へ拡張。 |
| #528 | #529 | 完了 | auth/superadmin 等の bypass ルートで access-log が orgId 未設定により ERROR を出していたのを是正（bypass 分岐で `orgId.set(0)`）。 |

**既知の後始末（handoff §4.2）:** フィールド `region`（sidebar/aside）はレコードで未配線のデッドコード（配線 or 撤去の判断保留）/ bundle の low 項目（divider 厳格検証・公開ページのサーバ CSP backstop 等）/ エディタのライブプレビュー（任意）。

---

## M16 — 公開サイトのテーマシステム（Epic #367・コア）: 完了（2026-06-20）

公開サイト（consumer フロント）の WordPress 風テーマ機能。**色だけ → 版面ごと変わる
＋管理者カスタマイズ＋ClaudeDesign で JSON 量産**まで到達。正本は
`docs/todo/handoff-2026-06-17-public-theme-system.md` ＋ `docs/theming/`。

| 領域 | PR（抜粋） | Summary |
| --- | --- | --- |
| ヘッダー設定 | #420 / #422 | feat: ヘッダー要素トグル・コンテンツ設定 |
| runtime テーマ基盤 | #425 / #428 / #429 | feat: DB manifest 駆動の runtime テーマ（backend / 公開 / FOUC 回避） |
| テーマサムネ | #430 | feat: テーマサムネのメディア指定 |
| ハイブリッドエンジン | #394 / #401 | feat: スタイルフラグエンジン v2 ＋ ビルトインテーマ量産 |
| feedColumns | #403 / #404 | feat: トップフィードの列数フラグ |
| カスタマイザ | #399 / #405 | feat: chrome 設定 ＋ カスタマイズのトースト |
| MCP bridge | #438 | feat: MCP bridge（OpenAPI 境界経由でテーマ操作を Claude.ai へ） |
| オーサリングガイド | #441 / #443 | feat: getThemeAuthoringGuide（サーバ検証由来の manifest 契約） |
| 描画モデル/フォント/CSS | #444 / #446 / #448 | feat: renderModel ＋ availableFonts ＋ getThemeEngineCss |
| ミニプレビュー | #450 / #452 | feat: テーマピッカーの実物ミニレンダリング |
| テーマ保存/詳細 | #454 / #460 / #462 | feat: 「テーマとして保存」/ 詳細モーダル＋明示適用 / 未保存変更ガード |
| reserved keys 同期 | #458 | fix: RESERVED_KEYS を全ビルトイン id に同期 |

**残（派生フェーズ・進行中）:** #372（画像ノブ・ライブプレビュー）/ #371（モーション層・残 View Transitions 等）/
#373（MD→テーマ パイプライン）。#390（ハイブリッド engine）はクローズ済み。詳細は上記「進行中エピック」。

---

## M15 — ウィジェット（Epic #324）: 完了（2026-06-13）

サイドバー/アサイド領域に置ける型付き・固定種類のウィジェット群（Elementor 路線は不採用）。

| Issue | PR | Summary |
| --- | --- | --- |
| #325 | #326 | feat: ウィジェット基盤 + 最近の投稿（region 配置・公開描画） |
| #327 | #328 | feat: メニューの location(header/footer/side) + メニューウィジェット |
| #331 | #332 | feat: 目次(TOC) — 見出しアンカー + 目次ウィジェット |
| #333 | #334 | feat: インライン目次（単一カラムの本文先頭に自動配置） |
| #335 | #336 | feat: サイト内検索（検索ウィジェット + `/search` 結果ページ） |
| #337 | #338 | feat: タグクラウド + タグアーカイブ `/tag/:slug` |
| #339 | #340 | feat: 人気記事 + アクセス集計API（`/api/v1/analytics/popular-entities`） |
| #341 | #342 | feat: カレンダー + 日付アーカイブ `/archive/:y/:m(/:d)` |
| #329 | #330 | chore(db): NENE2 サンプル `notes` テーブルを削除 |

**提供ウィジェット:** `recent-posts` / `menu` / `toc` / `search` / `tag-cloud` / `popular-posts` / `calendar`
**公開ページ追加:** `/search`、`/tag/:slug`、`/archive/:year/:month(/:day)`
**基盤追加:** entities 一覧に公開日レンジ絞り込み（`published_from`/`published_to`）

---

## M14 — 公開レイアウト & Custom Page: 完了（2026-06-12）

| Issue | PR | Summary |
| --- | --- | --- |
| #307 | #308 | feat: 公開ページのレイアウト基盤（型付きプリセット選択・Phase 1） |
| #309 | #310 | feat: 2/3カラム + フィールドの領域配置・並び順（Phase 2A） |
| #312 | #313 | feat: サニタイズ版 html フィールド型（CSS可/JS不可・Phase 2B） |
| #316 | #317 | feat: Custom Page 3A — bundle フィールド型（サンドボックス iframe 隔離）+ custom レイアウト |
| #318 | #319 | feat: Custom Page 3B — iframe 自動高さ(postMessage) + SEOテキスト必須 |
| #322 | #323 | fix: custom レイアウトの meta 必須を公開時のみに限定・失敗理由表示 |
| #320 | #321 | feat: フィールド領域(region)・表示順の管理UX仕上げ |
| #314 | #315 | feat(auth): セッショントークンを httpOnly Cookie へ移行（XSS耐性） |

---

## M13 — 管理コンソール再設計: 完了（2026-06-12）

| Issue | PR | Summary |
| --- | --- | --- |
| — | #298 | feat: 管理コンソールを Console 仕様に再設計・エンティティタイプ並び替え |
| — | #306 | docs(rules): design-export/ をローカル専用・非追跡とするルール追記 |

---

## M12 — メディアライブラリ強化: 完了（2026-06-12）

| Issue | PR | Summary |
| --- | --- | --- |
| #299 | #300 | feat(media): ストレージ抽象化 + Local/S3互換ドライバ（Phase A） |
| #299 | #301 | feat(media): 画像の寸法・alt テキスト・storage_key（Phase B） |
| #299 | #302 | feat(media): オンデマンド画像派生（プリセット + WebP/AVIF + キャッシュ・Phase C） |
| #299 | #303 | feat(media): 公開・管理でレスポンシブ画像（srcset・Phase D） |
| #304 | #305 | feat(media): メディアの使用箇所逆引き + 削除ガード |

---

## M11 — 通知・運用機能の拡充: 完了（2026-05-28）

| Issue | PR | Summary |
| --- | --- | --- |
| #263 | #267 | feat: 通知チャンネルモジュール（email/slack/discord/chatwork/webhook） |
| #273 | #280 | feat: 通知チャンネル管理 GUI |
| #264 | #292 | feat: コメントスパム対策（honeypot + IP/エンティティ別レート制限） |
| #285 | #295 | feat: Webhook を DB キュー＋ワーカー方式で非同期化 |
| #282 | #282 | feat: ユーザープロフィール編集・メールアドレス変更・プロフィール更新 |
| #283 | #294 | feat: メールアドレス変更の確認トークンフロー |
| #265 | #291 | feat: エンティティ一覧の検索クエリを URL(?q=) に反映 |
| #268 | #270 | fix: 組織作成時にデフォルトコンテンツタイプを自動シード |
| #269 | #269 | test(e2e): 完全シナリオ（ブログ・LP・ペルソナ 50 テスト） |
| #286 | #296 | test: feature フック単位の Vitest テスト拡充 |

---

## M10 — マルチテナント包括テストスイート + E2E 拡充: 完了（2026-05-27）

| Issue | PR | Summary |
| --- | --- | --- |
| #256 | #257 | test: マルチテナント包括テストスイート（PHPUnit 単体・1機能・ショート・完全シナリオ） |
| #260 | #259 | test(e2e): nene-corpus パターン参考の包括的ブラウザ E2E テストを追加する |
| #261 | — | docs: E2E 拡充後のドキュメント更新 |

**PHPUnit（545 tests, +171）:**
- `CapabilityMiddleware` org スコープテスト（8）
- `LoginUseCase` org_id 出力テスト（4）
- `ListUsersUseCase` org フィルターテスト（6）
- `CreateUserUseCase` org 割当テスト（6）
- `InviteUser` org 伝播テスト（6）

**Playwright E2E（157 tests, +90）:**
- 09-ui-states: ローディング・disabled・ダブルサブミット防止（6）
- 10-accessibility: ARIA 属性・ラベル・キーボード（11）
- 11-error-recovery: エラー回復フロー（10）
- 12-crud-flows: 複数作成・ナビ・削除確認（8）
- 13-role-access: admin/editor/superadmin 役割マトリクス（20）
- 14-pages: Comments/Navigation/Webhooks/Media/Settings/Dashboard（27）
- 15-repetition: 繰り返し安定性・長期フロー（8）
- 16-auth-lifecycle: Bearer ヘッダー・セッション・期限切れ・401（10）

---

## M9 — マルチテナント完成（1ユーザー=1組織）: 完了（2026-07-01）

| Issue | PR | Summary |
| --- | --- | --- |
| #251 | #252 | feat: マルチテナント基盤を実装する（DB・JWT・CapabilityMiddleware） |

**変更内容:**
- `users` テーブルに `organization_id` / `org_role` 追加（superadmin は NULL）
- `organizations` テーブルに `external_id` 追加（NeNe Corpus 将来連携用）
- `organization_users` テーブルを DROP（1ユーザー=1組織方針・未使用）
- JWT に `org_id` を埋め込み（superadmin = null、admin/editor = 所属 org ID）
- `CapabilityMiddleware` で JWT org_id vs 解決済み org_id を照合（不一致 → 403）
- `ListUsers` / `CreateUser` / `InviteUser` を org スコープ対応

---

## M8 完了（マージ済み）

| Issue | PR | Summary |
| --- | --- | --- |
| #211, #212 | #213 | feat: テナント解決モード設定 UI（single/subdomain/path） |
| #214 | #216 | feat: 既存データ組織割当 UI（シングル→マルチ移行） |
| #215 | #216 | feat: JSON 完全エクスポート／インポート（ID リマッピング付き） |

---

## M7 完了（マージ済み）

| Issue | PR | Summary |
| --- | --- | --- |
| #205, #206 | #210 | feat: organizations テーブル・CRUD API・OrgResolverMiddleware |
| #207 | #210 | feat: 全リポジトリの organization_id スコープ適用 |
| #208 | #210 | test: Organization HTTP テスト（13 テスト） |
| #209 | #210 | feat: スーパー管理画面（組織 CRUD React UI） |

---

## M6 完了（マージ済み）

| Issue | PR | Summary |
| --- | --- | --- |
| #200 | #201 | fix: Relation フィールド公開ページでパーマリンクパターンを使ったリンク生成 |
| #202, #203 | #204 | feat: コメント機能（投稿・一覧・モデレーション API ＋管理 UI ＋公開フォーム） |

---

## M5 完了（マージ済み）

| Issue | PR | Summary |
| --- | --- | --- |
| #190, #191 | #194 | feat: ユーザー管理 API・招待・パスワードリセット |
| #192 | #195 | feat: ユーザー管理フロントエンド（一覧・招待・パスワード変更） |
| #196, #197 | #198 | feat: メディアライブラリ（一覧・削除 API ＋管理画面） |

---

## 次フェーズ候補

| 項目 | 概要 | 難易度 | 優先度 |
| --- | --- | --- | --- |
| signup レート制限 | 公開セルフサーブ signup の abuse 対策（`ThrottleMiddleware` を `/api/v1/public/signup` に付与）。公開拡大の前提 | 小 | P1 |
| 設定の構造化 UI（#541） | 設定の生 JSON 露出を構造化フォームへ（#536 P1） | 中 | P1 |
| 権限細分化＋2FA（#543） | 権限粒度＋2FA＋パスワード忘れフォーム＋scoped API トークン（#536 P1） | 大 | P1 |
| 人気記事の精度向上 | per-view ロギング（entity_id 明示記録）で管理 GET を除外し正確化 | 中 | P2 |
| AI Consumer Chat | NeNe Records を外部 API として使うチャットシステム（**別リポジトリ**） | 大 | P2 |
| スケジュール公開改善 | `scheduled_at` ジョブ状態 UI・失敗ハンドリング | 小〜中 | P2 |
| ウィジェット表示順 UI | region 内のウィジェット並び替え（現状 display_order は固定 0） | 小 | P3 |

> 完了済み（旧候補）: コメント通知/スパム対策（#263/#264）、コンテンツ検索（#265 → 公開検索 #335 で実現）、**Consumer i18n（#540 で実現・2026-06-25）**。

---

## M4 完了（マージ済み）

| Issue | PR | Summary |
| --- | --- | --- |
| #145 | #148 | feat: Admin ダッシュボード（アクセス統計・公開数サマリー） |
| #146 | #149 | feat: API レート制限（IP ベース、120 req/60s） |
| #147 | #150 | feat: text_fields 多言語化（locale 列） |
| #151, #152 | #153 | feat: ステータスフィルター＋ロケール切替 UI |
| #154, #155 | #156 | feat: タグフィルター i18n・エクスポート URL・ページネーション改善 |
| #158 | #163 | refactor: サイドバーコンテンツ優先構造（Webhook を詳細設定へ） |
| #157 | #162 | refactor: WP 風用語統一（Content types / Items） |
| #159 | #164 | feat: entity_types に is_pinned フラグ追加 |
| #160 | #165 | feat: Posts・Pages デフォルトコンテンツタイプシード |
| #161 | #166 | feat: ピン留めタイプをサイドバーに動的表示・ダッシュボード改修 |
| #167 | #170 | feat: エンティティレコード管理 URL 短縮形 `/admin/:slug` |
| #168 | #171 | feat: 管理画面を `/admin/` 配下に移動・公開 URL から `/view/` 削除 |
| #172 | #173 | feat: デフォルトコンテンツタイプ多言語ラベルシード |
| #176 | #177 | feat: エンティティ一覧 body・作成/更新日時表示 |
| #174 | #175 | feat: エンティティ一覧ソート＋フィルターアコーディオン |
| — | #179 | feat: 管理画面フッター "Powered by NENE2 / © 2026 AYANE" |
| — | #181 | feat: フッター著作権を画面右下に固定表示 |
| — | #183 | feat: 管理画面テーマセレクター（5 テーマ） |
| — | #185 | feat: body フィールドを markdown 型に変更・Markdown エディター改善 |
| #186 | #187 | feat: 保存操作のトースト通知 |
| #169 | #188 | feat: WordPress 風パーマリンク設定（タイプ別 URL パターン・旧 URL リダイレクト） |

---

## M3 完了（マージ済み）

| 優先 | 項目 | Issue | PR |
| --- | --- | --- | --- |
| P1 | Admin UI リデザイン＋レスポンシブ | #127 | #128 |
| P1 | Full-text search API | #129 | #131 |
| P1 | Export API (CSV/JSON) | #130 | #132 |
| P2 | File field + upload | #133 | #134 |
| P2 | Webhook / event hooks | #135 | #136 |
| P3 | Scheduled publish | #137 | #138 |
| P3 | Draft preview token | #139 | #141 |
| P3 | Public cache / ETag | #142 | #143 |

---

## Verification

```bash
composer check                    # tests + PHPStan + CS-Fixer + OpenAPI + MCP
npm run check --prefix frontend   # type-check + lint + test + storybook
docker compose exec app vendor/bin/phinx migrate
docker compose exec app php tools/seed-blog-demo.php http://localhost
```
