# Changelog

All notable changes to NeNe Records are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
NeNe Records does not yet follow Semantic Versioning — entries are grouped by milestone.

---

## [ポスト #536 続 — Tier A インストーラ・移送経路・公開サイト堅牢化] — 2026-06-27 〜 2026-07-16

> ポスト #536（本番 SaaS 稼働）以降、**共有ホスティング向け Tier A インストーラ**（自己完結 zip）を
> GitHub Release として公開し、**SaaS org → Tier A の別インスタンス移送経路**を成立させ、公開サイト側の
> 堅牢化を連続出荷した。Issue/PR の詳細内訳と再開手順は `docs/todo/current.md` を正本とする。
> 版は **v0.5.0 → v0.5.2**（`VERSION` を単一ソース化）。本番は 2026-07-09 デプロイ（`php` 8.4.23 ピン）、
> 2026-07-13 に本節の main 差分の本番反映を施主 GO。07-14〜16 は AYANE サイト立ち上げと公開 SSR/SPA の
> 是正を連続出荷し、都度本番反映（デプロイ第29〜36回・すべて施主 GO）。

### Added
- **Tier A 共有ホスティング インストーラ（#707/#708）** — NENE2 `Nene2\Install` toolkit を配線した
  `public_html/install/`（要件チェック→DB/tenant→migrate→管理者→完了・再訪403）＋ `tools/build-release.sh`
  で自己完結 zip を生成し、**GitHub Release `v0.5.0`（66MB・Latest）**を公開。設置手順は `docs/install-tier-a.md`。
- **WP 式トップページ固定（front_page 設定、#701/#702）** — 任意の公開レコード1件を org 設定 `front_page` に
  ピン留めし公開トップ `/` で SSR（canonical=ルート・JSON-LD WebPage）、元 permalink は 302、sitemap を `/` に置換。
- **ページ階層／ディレクトリ（#656–#671）** — 公開 custom-permalink ページを実 SPA で解決・描画（#663）、
  ディレクトリ表示拡充＋titleless humanize（#664/#665）、DnD 親移動＋自動301・ツリー内検索・手動並び順（#666–#670）。
- **小説の章モード（なろう式、#646）** — 目次＋章レコード＋導出ナビ。本番 org `aozora` に漱石 1114 レコードを収録。
- **org 間移送経路（SaaS org → Tier A、#741）** — 衝突マージ＋id リマップ・トランザクション化・M8 以降の列追随・
  `tools/import-org.php`（CLI import）（#793 Phase 1）、menus/widgets/themes/blocks_fields/entity_relations/
  url_redirects の移送＋各 FK 再マップ（#794 Phase 2）、ブロック本文・`home_hero` の media 参照書き換え（#795）、
  `front_page` エンティティ id 再マップ（#801）。
- **org 単位の公開メンテナンスモード（#813）** — org ごとに公開ページをメンテ表示へ切替（管理 UI トグルは #814 で継続）。
- **公開サイトのフォント self-host（#818）** — Zen Kaku Gothic New / IBM Plex Mono を自前配信（外部 CDN 依存を排除）。
- **信頼済み外部埋め込みの per-org CSP allowlist（Phase 1、#802/#803）** — 公開ページ CSP に per-org の
  trusted-embed allowlist を導入（Phase 2 widget プリミティブ・Phase 3 media 同乗は継続）。
- **bare レイアウトの単一 html 描画（#799/#800）** — 1 html フィールドの bespoke ページを inline＋bare layout で忠実描画。
- **メンテナンスモードの管理 UI トグル（#814/#837）** — 一般設定に即保存スイッチ＋ON 時の注意表示（来訪者=メンテ表示／ログイン中=通常）。
- **org 移送カバレッジ拡充（#796/#838）** — comments / webhooks / webhook_deliveries / notification_channels /
  user_profiles を export/import 対象化（users は非移送・webhook secret は移送しない・user_profiles は email 突合）。
- **メディア実ファイルの zip 同梱移送（#798/#842）** — CLI 専用 zip 系統（`tools/export-org.php` / `tools/import-org.php`）で
  DB＋`var/media` 原本を単一 zip に。zip-slip / パストラバーサルはガード＋テスト済み。
- **trusted-embed 一級 widget プリミティブ（Phase 2、#802/#843）** — origin/src/SRI 必須の widget 型を保存側検証＋
  SSR（noscript シェル）＋SPA parity で描画。**#802 全 Phase 完了**。
- **配布 ZIP のフォントをオンデマンド化（#709/#841）** — base zip を約 14MB に削減。
- **エンティティタイプ一覧の SSR（#877/#878）** — `/posts` 等の型アーカイブをクロール可能に（aozora の `work` 1,114 件が初めて索引対象に）。
- **bespoke ページの SPA 遷移（#885/#886）** — 生 `<a>` を公開シェルの委譲クリックリスナで横取りしフルリロードを排除。
- **公開サイトの読み込み中表示（#894/#895）** — レイアウト未確定でも安全な上端バー(3px)＋中央3点。chrome・カラム数・テーマを推測描画しない。
- **X-Authorization フォールバック受け口を opt-in 有効化（#869）**。

### Changed
- **`VERSION` を 0.5.2 にバンプ（#808）** — 版の単一ソースを公開最新に一致（`/machine/health` も同値を報告）。
- **設定定義シードの systemic 修正（#711/#712）** — org 作成時に `setting_defs`（17定義）を自動投入＋バックフィル
  migration。signup/インストーラ産 org の設定 UI が空だった問題を解消。
- **superadmin コンソールを superadmin 専用に封鎖（#797/#805）** — 認証済みなら非 superadmin でも横断コンソール／
  org export・import API に到達できた緩さを capability で閉じる。
- **本番 compose に `var/media` 永続ボリューム（#807/#809）** — 再ビルドでのメディア消失を防止。
- **テーマサムネの配信（#705/#706）** — 本番 Apache が `/assets` のみ配信だった問題に `/theme-thumbnails` Alias を追加。
- **i18n の型権威を en → ja に反転（#870/#871・規約 04 I18N-8）**、非権威カタログは `satisfies Record<MessageKey>` 化（AI-23、#868）。
- **README を統一テンプレへ（#822/#823）** — 境界宣言新設・生数字撤去・Status 同期。
- **admin 一覧のレコードラベル正規化（#849/#850・#853/#854）** — 非 title フォールバックをタグ除去＋120字キャップに、
  meta_title を導出抜粋より優先（bare bespoke ページで一覧が 41,121px に爆発していた）。

### Fixed
- **派生画像が AVIF 無しビルド環境で全 500（#737/#738）** — GD が AVIF 非対応（共有ホスティングで実在）でも
  `Accept: image/avif` で 500 になっていたのを、encoder 能力込みの形式交渉（avif→webp→ソース形式）に是正。**v0.5.2 収録**。
- **superadmin org export/import API が常に 404（#739/#740）** — NENE2 Router のパラメータ受け渡し（PARAMETERS_ATTRIBUTE）
  非対応で `{id}` を読めなかったのを修正。実 Router 配線の HTTP テストを新設。**v0.5.2 収録**。
- **直リンク `/pages/{id}` が落ちる（#816/#817）** — SSR bootstrap の entity に permalink/slug を載せて解消。
- **再設置ガード素通し（#713）** — `.env` 無し早期 return が Docker 本番でガードを無効化していたのを probe 常時実行に。
- **共有ホスティングの env ブリッジ** — phpdotenv v5 が putenv しない環境で getenv 直読み設定が全て既定値化する問題を
  front controller で写して解消。**APP_BASE_PATH の入口 prefix strip** も是正。
- **公開 SSR/SPA のちらつき4連鎖（#879〜#888）** — 同じ症状に独立した原因が4つ:
  SSR が `layout` を無視し bare にも汎用 chrome を出力（#879/#880）／SPA が bootstrap から permalink 解決を
  seed していない（#881/#882）／型一覧クエリの seed キー不一致＋blocks の無条件フェッチ（#883/#884）／
  bootstrap の `entityTypes` が `{id,name,slug}` だけで `defaultLayout` 等が永久 undefined（#887/#888）。
  payload の形はキー集合の厳密一致でテスト pin。layout 未確定時の推測描画も全廃。
- **公開タイプ一覧のラベル parity（#891/#893）** — SPA が `getRecordDisplayLabel()` に `metaTitle` を渡しておらず
  人間にだけ `Record #1115` と壊れて表示。msw fixture を OpenAPI 契約と同形化し parity テスト5本を追加。
- **www→apex 301 の回帰（#832/#833・#834/#835）** — subdomain SaaS モードで `www.<base>` を予約し、301 が SPA シェルに飲まれないように。
- **パンくず/子一覧のラベルを導出＋meta_title 優先に正規化（#875/#876）**。
- **Zen Kaku Gothic New subset に OFL の著作権表示・条文を同梱（#872/#874）**。
- **default テーマの focus-ring を WCAG AA 3:1 へ是正（#864/#866）**・**codemod 写像表 v1 の silent no-op 群を契約語彙へ是正（#863/#865）**。

### Security
- superadmin コンソール／org export・import API を capability で保護（#797）。
- **自己診断 #824 の3連修正（#825/#827/#829）** — 未認証の管理 API 読取とクロステナント越境を封鎖、
  公開サイトの login バウンス回帰を是正、匿名の content read を published-only に限定。
- **webhook secret／通知チャネル config の機微キーを read で write-only 化（#836/#839・#845/#846）** —
  read から除去し `has_secret` / config 内 `has_<key>` で代替。省略更新は既存値維持・送信経路は不変。

---

## [ポスト #536 — UX/SEO/SSR・WordPress 移行・本番配備・subdomain SaaS] — 2026-06-24 〜 2026-06-26

> WordPress 比較×6ペルソナ討論を起点に公開 SEO/SSR を最優先化し、最大の残壁「非技術者の入口」を
> **subdomain 型マルチテナント SaaS の本番稼働（https://nene-records.com）**まで到達。Issue/PR の詳細内訳は
> `docs/todo/current.md`「ポスト #536」セクション、再開手順は `docs/handoff-2026-06-25.md` §A を正本とする。

### Added
- **公開 SEO/SSR 統合（Epic #537）** — SSR head に OGP/Twitter/canonical/JSON-LD（#544）、OG 画像を派生エンジンで自動生成し og:image 充填（#547/#548）、実 permalink でクローラブル SSR（#549）、単一オリジンで本番 SPA をマウント（#553）、`/view`→canonical 301＋SPA シェルフォールバック（#554）。方式＝PHP 前段＋SPA シェル。
- **WordPress 移行（WXR インポート、Epic #539）** — パーサ＋計画プレビュー（#561）、実行インポート（冪等、#562）、HTTP エンドポイント（#563）、管理画面 UI（#564）、301 リダイレクトマップ（#565）、メディア添付取込＋本文 URL 差替（#566）、Yoast/RankMath/AIOSEO の SEO メタ取込（#572）。
- **計測（GA4/GTM＋Consent Mode v2）** — nonce 付き head・CSP 条件緩和のバックエンド核（#583）、SPA ルート page_view＋同意バナー（#584）、同意既定のドロップダウン化（#589）。既定は analytics OFF（厳格 CSP）。
- **SEO 周辺** — `sitemap.xml`（正規 permalink・5 万 URL 上限、#585）、`robots.txt`（#587）、大規模時のインデックス分割（#588）。
- **WYSIWYG ライブプレビュー（Epic #538）** — ブロック編集（#590）とテーマカスタマイザ iframe（postMessage、#591）。
- **公開サイト多言語化（Epic #540）** — ロケール交渉＋hreflang（#592）、言語スイッチャ＋一覧のロケール解決（#593）。
- **relation フィールドのスキーマ UI**（#542/#550）。
- **本番配備・オンボーディング** — ワンコマンド初回導入（組織+admin、#573）、本番スタック＋fresh-DB migration P0 修正（#574）、多段 `Dockerfile.prod`（#575）、本番運用 3 点 healthcheck/backup/logs（#579）。
- **サブディレクトリ設置の base path 基盤** — バックエンド URL 前置（`APP_BASE_PATH`、#595）、フロント・ランタイム base（#596）、ディレクトリ方式マルチテナントの公開 SEO 配線（#599）。
- **subdomain マルチテナント SaaS（本番 cutover）** — on-demand TLS の ask エンドポイント（#600）、apex をグローバル面として通す（#601）、公開セルフサーブ signup（BE #602 / FE #603）、apex デフォルト・プロモ LP（#604）、cron を全テナント対応（#605）、`/`+html→SPA シェル（#606）、メール認証ソフトゲート（#608）、確認後にテナントログインへ案内（#609）。本番＝https://nene-records.com、`records.nene-suite.com`→301。

### Changed
- 単一オリジンの fallback を PSR-15 `SingleOriginKernel` に集約（#568）。WXR 応答を OpenAPI 名前付きスキーマで型付け（#569）。
- 公開 HTML レスポンスの CSP を SPA 対応へ緩和（厳格性は維持、#557/#558）。ランタイム base を `<base href>` 由来に統一し CSP ブロックを解消（#597）。
- README を実態へ整合（NENE2 sibling-clone 明記、#580）。
- 検証基準（2026-06-26 実測）: PHPUnit **1038 tests / 2887 assertions**、PHPStan level 8（1250 files・エラー 0）、MCP **69 tools**、frontend vitest **418 tests（84 files）**、Playwright E2E 220 tests（20 files）。

### Fixed
- org 未解決パスで `UrlRedirectResolver` が落ちる本番バグを best-effort 化（#577）。
- 本番 compose の `MAIL_DSN` 空既定で全リクエストが 500 になる問題を null transport 既定で解消（#578）。
- 単一 org デプロイの org-not-resolved（`system_config` 空 seed × `ORG_SLUG` フォールバック、`??`→`?:`、#582）。
- SSR で html 型をサーバ側サニタイズ（#570）、WXR 取込本文を必ず html 型フィールドへ格納（#571）、メール送信の env キーを `MAIL_FROM_ADDRESS` に是正（#607）。

### Security
- WXR メディア取得の SSRF を遮断し内部アドレスへの到達を拒否（#567）。
- 計測の inline スクリプトは `'unsafe-inline'` でなく per-response nonce で許可（既存スクリプト厳格性を維持、#583）。

### Docs
- 市場ペルソナ討論 第 1〜6 回の議事録＋本番デプロイ実機検証レポート（#535/#546/#552/#556/#560/#576/#581/#594/#598/#610）。

---

## [ポストM16 — リッチページ／ブロック・型チェック是正・i18n 6言語化] — 2026-06-20 〜 2026-06-24

> M16 後 main へマージした作業。Issue/PR の詳細内訳は `docs/todo/current.md`「ポストM16」セクションを正本とする。

### Added
- **型付き投稿ブロック（Epic #486）** — blocks フィールド型のバックエンド基盤（#487）＋本文ブロック 5 種 `text/callout/hero/gallery/chart`＋ホーム hero（#488）。第一者描画（任意 JS 無し）/ サーバ検証 / sr-only SEO 投影 / URL allowlist。
- **リッチページ化（Epic #491 / Custom Page #311）** — コンテンツタイプのスターター項目（Rich page=blocks 本文、#492）、レイアウトブロック `group/columns/spacer/divider`（#493/#494/#495）、dual-SEO bundle（#496）、`text_fields.value` を LONGTEXT 化（#498）、full-bleed custom レイアウト（#497）、Custom page スターター（#499）。
- **モーション/インタラクション層（#371）** — 能力レイヤ基盤＋scroll-reveal（#477/#478）、ヘッダー sticky-shrink（#479/#480）、hero バリアント split/gradient（#481/#482）。
- **テーマカスタマイザ（#372）** — 見出し/モノスペースフォントのノブ（#476）、ヒーローのコンテンツ編集 UI（#485）。

### Changed
- **フロントエンド全体監査の remediation（Epic #507・#508–#531）** — 管理画面 6 言語化（en/ja/de/fr/pt-BR/zh-Hans、#530/#531）、フォーム a11y（useId）、状態色のセマンティックトークン化、公開フォーマッタ集約、theme query-key の factory 化、vendor 分割で初期チャンク 620→209kB、毎キーストローク PUT のローカルドラフト化ほか。
- **ブロック関連フロントの構造リファクタ（#501・#502–#514）** — 規約整合・重複排除・god 分割・UI 共通化（Modal/Textarea/IconButton 等）。

### Fixed
- 型チェックが no-op だった問題を是正し隠れていた tsc エラー 1197→0（#489/#490）。
- タグ slug の一意制約を `(organization_id, slug)` 複合へ是正（#464/#465）。
- bypass ルートで access-log が orgId 未設定により ERROR を出していたのを解消（#528/#529）。
- Webhook 配信ワーカーを cron に配線し cron トリガー API を公開（#466/#467）。

### Security
- アップロード SVG の深サニタイズ＋配信ハードニングで stored XSS 経路を遮断（#474/#475）。

### Docs
- ClaudeDesign 向け MCP 実践ガイド（#435/#436）。current.md のドキュメントドリフト解消（#468/#469・#532/#533）。

---

## [M11–M16 サマリー] — 2026-05-28 〜 2026-06-20

> このまとめは CHANGELOG が M10 で停止していたドリフトの補填（#468）。各マイルストーンの
> Issue/PR 内訳は `docs/todo/current.md` を正本とする。以降は同 file を都度更新すること。

### Added
- **M11 通知・運用** — 通知チャンネル（email/slack/discord/chatwork/webhook、#263/#267）、管理 GUI（#273/#280）、Webhook 非同期化（DB キュー＋ワーカー、#285/#295）、コメントスパム対策（#264/#292）。
- **M12 メディアライブラリ強化** — ストレージ抽象化 Local/S3（#299/#300）、寸法/alt/storage_key（#301）、オンデマンド派生（WebP/AVIF＋キャッシュ、#302）、レスポンシブ srcset（#303）、使用箇所逆引き＋削除ガード（#304/#305）。
- **M13 管理コンソール再設計** — Console 仕様への再設計・タイプ並び替え（#298）。
- **M14 公開レイアウト & Custom Page** — 型付きレイアウトプリセット（#307/#308）、2/3カラム領域配置（#309/#310）、サニタイズ html フィールド（#312/#313）、bundle フィールド＋サンドボックス iframe（#316/#317）、iframe 自動高さ＋SEO テキスト（#318/#319）、セッションを httpOnly Cookie へ（#314/#315）。
- **M15 ウィジェット（Epic #324）** — recent-posts/menu/toc/search/tag-cloud/popular-posts/calendar、公開ページ `/search`・`/tag/:slug`・`/archive/:y/:m(/:d)`（#325〜#342）。
- **M16 公開サイトのテーマシステム（Epic #367・コア）** — runtime テーマ（DB manifest 駆動、#425/#428/#429）、ハイブリッドなスタイルフラグエンジン（#394/#401）、カスタマイザ（#399/#405）、MCP bridge（#438）、オーサリングガイド/描画モデル（#441/#444/#446/#448）、テーマ保存/詳細モーダル/未保存ガード（#454/#460/#462）。
- 名前付きメニュー移行（Epic #347）とレイアウトビルダー（Epic #352）を完了。

### Changed
- 検証基準: PHPUnit 545 → **764 tests**、MCP **83 tools**、Playwright E2E 157 tests を維持。

---

## [E2E テスト拡充 — 包括的ブラウザシナリオ] — 2026-05-27

### Added
- `09-ui-states.spec.ts` (6 tests) — ローディング中ボタンテキスト変化・フォーム disabled・ダブルサブミット防止・エラーメッセージ重複なし確認 (#260, PR #259)
- `10-accessibility.spec.ts` (11 tests) — ARIA 属性・ラベル紐付け・キーボードナビゲーション・スクリーンリーダーアクセシブルなエラー状態 (#260, PR #259)
- `11-error-recovery.spec.ts` (10 tests) — entity-types/tags/users ロードエラー→Retry→成功・POST エラー→リトライ→アイテム表示 (#260, PR #259)
- `12-crud-flows.spec.ts` (8 tests) — 複数作成・フィールドdefs ナビゲーション・削除確認ダイアログ・キャンセルで保持 (#260, PR #259)
- `13-role-access.spec.ts` (20 tests) — admin/editor/superadmin 役割×ページの網羅的アクセスマトリクス (#260, PR #259)
- `14-pages.spec.ts` (27 tests) — Comments・Navigation・Webhooks・Media Library・Site Settings・Dashboard 各ページの完全カバレッジ (#260, PR #259)
- `15-repetition.spec.ts` (8 tests) — N 連続作成安定性・フォームリセット確認・エラー/成功交互サイクル・ダブルサブミット長期安定性 (#260, PR #259)
- `16-auth-lifecycle.spec.ts` (10 tests) — Bearer ヘッダー伝播・localStorage トークン永続・期限切れリダイレクト・401 による自動ログアウト (#260, PR #259)

### Changed
- Playwright E2E 合計: 67 → 157 tests (#260, PR #259)

---

## [M10 — マルチテナント包括テストスイート] — 2026-07-01

### Added
- PHPUnit `CapabilityMiddlewareTest` — org_id 一致・不一致・superadmin バイパス・非 int 属性・JWT org_id なし等 8 ケース追加 (#256, PR #257)
- PHPUnit `LoginUseCaseTest` — admin/editor の org_id 出力・superadmin は null 等 4 ケース追加 (#256, PR #257)
- PHPUnit `ListUsersUseCaseTest` (新規) — organizationId=null 全件・org 絞り込み・クロス org 分離・ソート 6 ケース (#256, PR #257)
- PHPUnit `CreateUserUseCaseTest` (新規) — org 割当あり・なし・orgRole デフォルト・クロス org 分離 6 ケース (#256, PR #257)
- PHPUnit `InviteUserMultitenancyTest` (新規) — 招待ユーザーへの org 伝播・listByOrgId への反映 6 ケース (#256, PR #257)
- Playwright Level 2 (1機能) — org フィールド含む API レスポンスでも UI 正常描画・org 更新/削除 API コール確認 (05-09〜11, 06-10-*) (#256, PR #257)
- Playwright Level 3 ショートシナリオ (新規 `07-multitenancy-short.spec.ts`) — 6 tests (#256, PR #257)
- Playwright Level 4 完全シナリオ (新規 `08-multitenancy-full.spec.ts`) — 7 tests: クロス org 分離・ロールアクセスマトリクス・superadmin ライフサイクル (#256, PR #257)
- `api-mocks.ts` に org 付きモックデータ追加 (`USER_LIST_WITH_ORG`、`USER_LIST_ORG2`、`INVITE_USER_RESPONSE_WITH_ORG`、`ADMIN_LOGIN_WITH_ORG_ID` 等) (#256, PR #257)
- `bypassLogin()` ヘルパーに `orgId?` オプション追加 (#256, PR #257)

### Changed
- PHPUnit 合計: 374 → 545 tests (#256, PR #257)
- Playwright 合計: 48 → 67 tests (#256, PR #257)

### Fixed
- `PreviewTokenUseCaseTest` の PHPStan `nullsafe.neverNull` 警告を修正 (#256, PR #257)
- `NavigationItemUseCaseTest`・`TextFieldDeleteUpdateUseCaseTest` の CS Fixer import 順を修正 (#256, PR #257)

---

## [M9 — マルチテナント完成（1ユーザー=1組織）] — 2026-07-01

### Added
- `organization_id` column on `users` table — non-superadmin users are bound to exactly one organization; superadmin users have `NULL` (#251, PR #252)
- `org_role` column on `users` table — organization-level role (`admin` / `editor`) stored alongside the global role (#251, PR #252)
- `external_id` column on `organizations` table — unique nullable identifier for NeNe Corpus and future external system integration (#251, PR #252)
- JWT `org_id` claim — `LoginUseCase` embeds the user's organization ID in the token at login; superadmin receives `null` (#251, PR #252)
- `CapabilityMiddleware` org scope enforcement — validates that JWT `org_id` matches the request-resolved org context (`nene2.org.id`); returns 403 `org-access-denied` on mismatch; superadmin bypasses the check (#251, PR #252)
- `listByOrganizationId()` and `updateOrganization()` on `PdoUserRepository` and `UserRepositoryInterface` (#251, PR #252)
- Org-scoped user listing — `ListUsersHandler` returns only same-organization users for admin/editor JWT tokens; superadmin sees all (#251, PR #252)
- Org-auto-assignment on user create and invite — `CreateUserHandler` and `InviteUserHandler` resolve organization from `nene2.org.id` request attribute (org-scoped routes) or `organization_id` body field (superadmin direct) (#251, PR #252)

### Removed
- `organization_users` join table — previously unused; allowed multiple org memberships and conflicted with the 1-user-1-org policy (#251, PR #252)

### Changed
- All Organization CRUD (`CreateOrganization`, `UpdateOrganization`, `GetOrganizationById`, `ListOrganizations`) — Input / Output / UseCase / Handler updated to expose and persist `external_id` (#251, PR #252)
- `GetUserById`, `ListUsers`, `CreateUser` API responses now include `organization_id` and `org_role` fields (#251, PR #252)
- Login API response now includes `org_id` field (#251, PR #252)

---

## [M8 — マルチテナント運用機能] — 2026-05-27

### Added
- Data Migration UI — `GET /api/v1/superadmin/data-migration/status` returns unassigned record counts per table; `POST /api/v1/superadmin/data-migration/assign-org` bulk-reassigns `organization_id=0` records to a target org (#214, PR #216)
- Superadmin Data Migration page — shows per-table unassigned counts, org selector, and "Assign Records" action; accessible from Superadmin sidebar (#214, PR #216)
- JSON Export — `GET /api/v1/superadmin/organizations/{id}/export` exports all tenant-scoped data (entity types, entities, field values, tags, navigation, settings, media) as a downloadable JSON payload (#215, PR #216)
- JSON Import — `POST /api/v1/superadmin/organizations/{id}/import` imports an export payload; all primary keys are remapped to new auto-increment values; cross-table references are resolved via ID mapping; globally unique tag slugs are reused if already present (#215, PR #216)
- Export / Import buttons on Organization Detail page — "Export JSON" triggers file download; "Import JSON" opens file picker and POSTs the parsed payload (#215, PR #216)
- `IconDatabase` and `IconDownload` SVG icons added (#216)
- Tenancy resolution mode selector — `GET/PATCH /api/v1/superadmin/system-config`; `system_config` table stores `tenant_resolution_mode`, `tenant_org_slug`, `tenant_base_domain`; `RuntimeServiceProvider` reads from DB with env fallback (#211, #212, PR #213)
- Superadmin Settings page — radio group for single / subdomain / path mode with conditional aux inputs (#211, PR #213)

---

## [M7 — マルチテナント基盤・スーパー管理画面] — 2026-05-27

### Added
- `organizations` table — `id`, `name`, `slug` (unique), `custom_domain`, `plan` (`free`/`starter`/`pro`/`enterprise`), `is_active`, `created_at`, `updated_at` (#205, PR #210)
- Organization CRUD API — `GET /api/v1/superadmin/organizations`, `POST`, `GET /{id}`, `PATCH /{id}`, `DELETE /{id}`; superadmin only (#206, PR #210)
- `OrgResolverMiddleware` — resolves `organization_id` from `X-Organization-Slug` request header; shares value via `RequestScopedHolder<int>` injected into all repositories (#205, PR #210)
- Row-level org isolation — `organization_id INTEGER NOT NULL DEFAULT 0` added to all field/media/navigation/setting tables; all PDO repositories filter and insert by `organization_id` (#207, PR #210)
- `superadmin` role and `manage_organizations` capability (#205, PR #210)
- Organization HTTP tests — 13 tests covering CRUD happy paths and error cases using `InMemoryOrganizationRepository` (#208, PR #210)
- Superadmin React UI — `SuperadminShell` layout + `OrganizationsPage` (list / create / delete) + `OrganizationDetailPage` (edit / danger-zone delete); route tree `/superadmin/**` guarded by `RequireAuth` + superadmin check (#209, PR #210)
- AppShell: superadmin navigation link visible only to superadmin users; `IconBuilding` SVG icon (#209, PR #210)

---

## [M6 — コメント機能] — 2026-05-27

### Added
- Comment submission API — `POST /api/v1/entities/{id}/comments` (公開); submitted comments are stored with `is_approved=false` and await moderation (#202, PR #204)
- Public comment list API — `GET /api/v1/entities/{id}/comments` returns approved comments only, ordered by `created_at ASC`; `author_email` omitted from public response (#202, PR #204)
- Admin comment management API — `GET /api/v1/admin/comments`, `PATCH /api/v1/admin/comments/{id}/approve`, `DELETE /api/v1/admin/comments/{id}`; requires `ManageSettings` capability (#202, PR #204)
- Comment public UI — `CommentSection` component on public record detail pages: approved comment list + form (name, email, body) with pending-moderation message (#203, PR #204)
- Comment admin UI — `/admin/comments` page with pending/approved badges, approve button, delete confirmation; accessible from AppShell Appearance section (#203, PR #204)
- `IconMessageCircle` SVG icon (#203, PR #204)
- i18n: admin and public comment keys in `en.ts` / `ja.ts` (#203, PR #204)

---

## [M5 — ユーザー管理・メディアライブラリ] — 2026-05-27

### Added
- User management API — `GET/POST /api/v1/users`, `GET/PATCH/DELETE /api/v1/users/{id}`, `POST /api/v1/users/{id}/admin-reset-password`, `PUT /api/v1/users/me/password`; admin/editor role enforcement (#190 #191, PR #194)
- User invite flow — `POST /api/v1/user-invites` sends invite email with one-time token; `POST /api/v1/user-invites/accept` activates account (#190 #191, PR #194)
- Password reset flow — `POST /api/v1/user-invites/request-password-reset` (anti-enumeration 204); `POST /api/v1/user-invites/confirm-password-reset` exchanges token for new password (#190 #191, PR #194)
- `MailerInterface` + `SymfonyMailer` using Mailpit in development (#191, PR #194)
- User management Admin UI — user list, invite form, role change, admin password reset, self-service password change, delete with confirmation (#192, PR #195)
- Accept-invite public page `/admin/accept-invite?token=…` and reset-password public page `/admin/reset-password?token=…` (#192, PR #195)
- Media library list API — `GET /api/v1/media` returns all uploads ordered by `created_at DESC`; `created_at` added to upload response (#196, PR #198)
- Media delete API — `DELETE /api/v1/media/{id}` removes DB record and best-effort unlinks physical file (#196, PR #198)
- Media library Admin UI — drag-and-drop upload, thumbnail grid, copy-URL, delete with confirmation; `/admin/media` route (#197, PR #198)

---

## [M4 — 機能拡充・使い勝手改善] — 2026-05-26

### Added
- Admin dashboard — `/api/v1/dashboard` returning recent published entities, today / month access counts, entity type summary (#145, PR #148)
- IP-based API rate limiting — `ThrottleMiddleware` + `PdoRateLimitStorage`; 120 req/60s default; `429 Too Many Requests` + `Retry-After` / `X-RateLimit-*` headers (#146, PR #149)
- Multi-language content — `locale` column on `text_fields`; locale filtering on list / create / update APIs (#147, PR #150)
- Entity list status filter — All / Draft / Published / Scheduled / Archived toggle buttons on the records management screen (#151, PR #153)
- Locale switcher on field edit screen — Default + per-locale buttons; free-text locale input; locale-aware create / update mutations (#152, PR #153)
- `scheduled` status badge (blue) on entity list items — previously missing
- Tag filter i18n, export URL status reflect, pagination UI improvements (#154 #155, PR #156)
- Sidebar content-first restructure — Webhooks moved to advanced settings (#158, PR #163)
- i18n term unification — WordPress-style Content types / Items (#157, PR #162)
- `is_pinned` flag on entity types — dynamic navigation foundation (#159, PR #164)
- Default content types seed — Posts & Pages (#160, PR #165)
- Pinned entity types in sidebar, dashboard revision (#161, PR #166)
- Short-form entity record management URLs `/admin/:slug` (#167, PR #170)
- Admin moved to `/admin/` prefix; public URLs stripped of `/view/` (#168, PR #171)
- Default content type multilingual labels seed (#172, PR #173)
- Entity list body / created_at / updated_at columns (#176, PR #177)
- Entity list sort + filter accordion (#174, PR #175)
- Admin footer — "Powered by NENE2 / © 2026 AYANE" (#179, PR #179)
- Admin footer fixed bottom-right copyright display (#181, PR #181)
- Admin theme selector — Default / GitHub / Solarized / Dracula / Monokai (#183, PR #183)
- Markdown editor improvements — body field type, toolbar icons (#185, PR #185)
- Toast notifications for save operations (#186, PR #187)
- WordPress-style permalink settings — per-entity-type URL pattern with presets & custom tokens, old-URL redirect, `{type}` collision warning (#169, PR #188)

---

## [M3 Complete — Headless CMS Platform] — 2026-05-26

### Added
- Admin UI redesign — sidebar layout, dark theme, responsive mobile drawer (#127, PR #128)
- Full-text search API — `/api/v1/entities/search?q=` with entity type scoping (#129, PR #131)
- Export API — CSV / JSON download per entity type with field columns (#130, PR #132)
- File field type — non-image file upload support extending the media upload API (#133, PR #134)
- Webhook / event hooks — CRUD for webhook subscriptions; HMAC-SHA256 signed POST on `entity.created` / `.updated` / `.deleted` (#135, PR #136)
- Scheduled publish — `scheduled_at` + `status: scheduled`; `POST /api/v1/entities/process-scheduled` batch transition (#137, PR #138)
- Draft preview token — 64-char hex token URL for non-published record preview; 24-hour TTL with rotation (#139, PR #141)
- Public cache headers — `Cache-Control` / `ETag` / `304 Not Modified` on public consumer endpoints (#142, PR #143)
- Docker Compose `cron` service — calls `process-scheduled` every minute via busybox crond

---

## [M2 Complete — Team-Ready CMS] — 2026-05-26

### Added
- Admin / editor roles with JWT-embedded `role` and `CapabilityMiddleware` on mutating routes (#108, PR #111)
- Entity archive before entity type purge — JSON snapshot + CSV download API (PR #107)
- Default user seed migration — `admin@nene-records.local` / `editor@nene-records.local` (#108)
- Admin UI role-based nav and button visibility; 403 → `/forbidden` page (#108)
- PHP / TypeScript naming convention document; renamed all files to match (#113, PR #114)
- Admin i18n foundation — 6-language message catalog (`ja` / `en` / `zh` / `ko` / `fr` / `de`) and locale switcher (#109, PR #115)
- Admin full-screen i18n migration — all hardcoded strings moved to `t()` calls (#110, PR #116)
- Entity record revision history — action log snapshot on create / update / delete (#117, PR #118)
- Per-record SEO fields — `meta_title` / `meta_description` on entity records (#119, PR #120)
- Image field type and media upload API — multipart POST, type/size validation, local storage (#121, PR #122)
- Markdown field type and Admin preview editor (#124, PR #123)
- Navigation settings CRUD API and Admin management screen (#125, PR #124)
- `CHANGELOG.md` — this file (#94)
- `SECURITY.md` — vulnerability reporting policy (#97)
- `llms.txt` — LLM crawler summary (#96)
- README badges and OSS-ready copy (#98)

### Fixed
- Entity type delete failing with 500 when soft-deleted entities existed (FK RESTRICT + count mismatch) (#106, PR #106)
- ConfirmDialog not clearing delete mutation error on reopen (#106)

---

## [M1 Complete] — 2026-05-24

### Added
- PHP backend GitHub Actions CI (`composer check`: PHPUnit / PHPStan / CS-Fixer / OpenAPI / MCP) (#90, PR #99 #101)
- User authentication, Admin login, API Bearer auth with JWT (#86, PR #88)
- Public slug routing — `/view/{type}/{slug}` and `slug` field on entities (#87, PR #89)
- Publish status workflow — `draft` / `published` / `archived` + `published_at` (#84, PR #85)
- Site Settings API and Admin UI — 3-table design with atomic updates (#80, PR #81)

### Fixed
- `expires_at` returned as UNIX timestamp integer instead of ISO 8601 string (PR #100)
- Backend CI failing due to missing local `../NENE2` path in GitHub Actions (PR #101)

---

## [Phase 7 — CMS Hardening] — 2026-05-24

### Added
- Public record aggregation API and HTML bootstrap (#75, PR #76)
- Markdown rendering for `body` fields on public pages (#78, PR #79)
- Blog demo seed script (`tools/seed-blog-demo.php`) (PR #77)
- CMS mid-term milestone plan and TODO (PR #83)

---

## [Phase 6 — Consumer Views] — 2026-05-24

### Added
- Consumer views — public listing and detail pages (#38, PR #39)
- Consumer view relation link display (#63, PR #64)
- Public record detail: relations rendered as links (PR #64)
- Access log persistence and daily analytics API (#69, PR #70)
- Consumer Views Phase 6 enhancements — layout, field display (#72, PR #73)

---

## [Phase 5 — MCP Tools] — 2026-05-24

### Added
- Full API MCP tool catalog — 60 tools across all domains (#67, PR #68)
- Entity relation tools added to MCP catalog (#65, PR #66)

---

## [Phase 4 — Entity Relations & Tags] — 2026-05-24

### Added
- Entity relations API and `relation` field type for field_defs (#51, PR #54)
- ADR 0004: Entity Relations design (#51, PR #53)
- Record relation attach/detach UI (#52, PR #56)
- Records list: relation filter API and Admin UI (#57 #59, PR #58 #60)
- Record detail: inverse relation (referenced-by) list (#61, PR #62)
- Tag CRUD Admin UI (#44, PR #45)
- Record tag attach/detach UI (#46, PR #47)
- Records list: tag filter UI (#48, PR #49)
- Records list: relation filter UI (#59, PR #60)
- List API: relation filter (#57, PR #58)

---

## [Phase 3 — Field Types & Filters] — 2026-05-24

### Added
- All field type UIs: text, int, bool, enum, datetime (#36, PR #37)
- List API field filters for all types (PR #37)
- field_def edit UI (#40, PR #41)
- text-fields: entity_type_id filter + list label optimisation (#42, PR #43)
- Frontend CI (ESLint / TypeScript / Vitest) (PR #37)

---

## [Phase 2 — Core Admin UI] — 2026-05-24

### Added
- Entity type editor — create, list, delete (#24, PR #25)
- Entity list, create, delete UI (#26, PR #27)
- field_defs list, create, delete UI (#28, PR #29)
- text_fields value edit UI (#30, PR #31)
- Record list with title display (#32, PR #33)
- int_fields value edit UI (#34, PR #35)

---

## [Phase 1 — Infrastructure] — 2026-05-24

### Added
- Local development Docker Compose stack (MySQL 8.4 + phpMyAdmin + Mailpit) (#22, PR #23)
