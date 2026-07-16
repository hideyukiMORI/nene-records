# Current Work

Last updated: 2026-07-16

## 最新: 公開一覧ラベル parity 修正＋読み込み中デザイン＋AYANE 残ブロッカー消化（2026-07-16・**本番反映済み**）

> 施主の「エンティティー一覧機能ってなかったっけ？」という確認から、**公開タイプ一覧のタイトルが
> クローラには正しく人間には壊れて出ている**ことが判明（#891）。あわせて読み込み中表示をデザインし
> （#894）、AYANE の残ブロッカー2件（`/blog` 実体化・`/for-accountants` 除去）を消化。
> **2 PR マージ・本番デプロイ2回（第35〜36回）＋DB内容変更2件・全て CI 緑・実ブラウザで検証済み。**
> 詳細 = `docs/daily/2026-07-16.md`

### #891 / PR #893 — 公開一覧のラベル parity（#849/#853/#875 の**3つ目**の双子）

| | 人間（SPA） | クローラ（SSR） |
|---|---|---|
| aozora `/work` 最新20件 | `Record #1115` | 『文学論』序 |
| AYANE `/pages` 全12件 | 共通ナビの HTML 全文（**12件とも同一**） | セキュリティと品質（Trust）｜彩音… |

- **配線漏れ**（#879/#880 と同型）。`getRecordDisplayLabel()` は `title → metaTitle → derived
  excerpt → fallback` の順で解決するのに、呼び出し側が **`metaTitle`（第5引数）を渡していなかった**。
  PHP の双子 `RecordDisplayLabel::resolve` は docblock で「順序は意図的に同一」と宣言し渡している。
- ★**本番 API は正しい値を返していた**（`id=1115 → meta_title='『文学論』序'`）＝**SPA は正しい題名を
  手に持ったまま捨てていた**。
- **なぜテストで捕まらなかったか**: msw の `EntityRecord` が OpenAPI の `EntityResponse` と同形でなく、
  **必須の `meta_title` / `meta_description` / `layout` / `scheduled_at` が4つとも欠けていた**。
  契約に合わせて補い、**落とすと本番と同じ文字列で失敗する parity テスト5本**を追加。

### #894 / PR #895 — 読み込み中表示のデザイン

施主指定＝「**メインは上部のバーのアニメーション**」＋文字案 **T1（中央に薄く）**。ClaudeDesign に依頼し2ラウンドで確定。

- **A群（レイアウト未確定）は無描画を維持**し、ビューポート基準の上端バー(3px)と中央の3点だけを浮かせる。
  chrome もカラム数もテーマの有無も仮定しない（**間違ったレイアウトは壊れたサイトに見え、空白は
  読み込み中に見える** = #879/#881/#883/#887 の教訓）。
- ★**A群のセレクタに `.nene-public` を付けてはいけない**。`.nene-public` と `data-theme` を張るのは
  `PublicSiteShell` で **`bare` はそこを通らない**＝スコープすると **bespoke サイトで丸ごと効かない**。
  B群（レイアウト確定後のスケルトン）は逆にスコープが正しい。
- **S9（`PublicShell` の `<Suspense fallback={null}>`）を新たに拾った**＝常時mount・レイアウト判定前・
  `.nene-public` の外＝**本来の置き場**。1コンポーネントが4状態を賄う。
- **S3 の違反を撤去**（レイアウト未確定なのにテーマ chrome を描き、公開側で唯一 `Loading…` を英語直書きし、
  読み込み中に「Back to latest」ボタンを出していた）。
- **「何を描かないか」をテストで pin**（header/footer/nav/a が無い・`.nene-public` 配下でない）。

### AYANE — 残ブロッカー2件を消化

- **`/blog` 実体化**（entity 1150・slug=blog・layout=bare）。**施主裁定=「一次的に固定ページ」**。
  型アーカイブは不採用（実測: bespoke org のアーカイブは nav 0 / footer 0 / ブランド色 0 / フォント 0）。
  **chrome は本番 `blog-renewal` の実バイトを流用**し `<article>` だけ差し替え（48KB中の新規は5%）。
  ★`text_fields` に `created_at`/`updated_at` は**無い**（`entities` にはある）。
- **`/for-accountants` 導線を全除去**（施主GO）。対象は**2種類**あった: フッタのリンク（全13ページ）＋
  **トップの「FOR ACCOUNTANTS」セクション丸ごと**（本文が「登録なしでダウンロードいただけます」と
  約束していたため節ごと）。**AYANE の壊れたリンクは 2 → 0**。
- **日付の三重不一致は直さない**（表示 `2026.07.25` / JSON-LD `2026-07-11` / 実公開 `07-19`）＝
  **施主判断「ayane.co.jp に完全移行するときに辻褄を合わせればいい。優先事項の最下位」**。

### ~~🔴~~ #896 — `/downloads/*.pdf` が一度も配信されていない（**contact 開通の前提**）→ **クローズ（07-16 同日）**

> **追記（同日クローズ）**: 施主 GO 後の着手時調査で、**3PDF は 07-12 01:35 に media アップロード済みだった**
> ことが本番実測で判明（下記の前提「未アップロード」が古かった）。media URL 3本は 200/application/pdf・
> contact 側の実測 URL とも突合一致 → 受け入れ条件全達でクローズ。詳細は「残（open）」の同項を参照。

- ビルド済みの `pricing` / `company-profile` / `terms` の**3PDFも全部ソフト404**。本番 Apache の Alias は
  **`/assets` と `/theme-thumbnails` の2つだけ**（#705 と同型・**dev は Vite が配るので露見しない**）。
- ★`pdf_docs.py` の docstring 曰く「**自動返信メール（contact 側）が実URLで参照するゲートなし資料**」
  ＝**contact を開通させると自動返信が 404 を3本張ったまま顧客に届く**。
- **施主判断 = A（media にアップロード）**。`application/pdf` は許可済・media volume は恒久化済・
  **org スコープ＝製品コアに焼き込まない**。B案（Apache Alias）は #873 と同じ構造問題を増やすため不採用。
- 備忘録は**3経路**: `_work/ayane-site-build/for-accountants-pdf-content.md` 冒頭 / `_work/issues.md` #50 / 本 issue。

### 残（open）

**AYANE 公開 = 07-19（残り3日）。残ブロッカーは contact フォーム開通（別リポ）のみ — その前提だった #896 は
07-16 にクローズ（3PDF は 07-12 から media 配信済みと判明・確定 URL を contact へ連携済み）。**
→ 一覧は下の **[## 残（open）— このリストが正](#残open--このリストが正2026-07-16-時点)** に集約（**2箇所に書くと必ずずれる**ため、ここには置かない）。

---

## 2026-07-15: AYANE 内側デザイン反映＋公開SSR/SPA の連続修正（**本番反映済み**）

> ClaudeDesign から AYANE 内側11ページのデザインが届き本番反映。その検証中に**公開サイトの
> SSR/SPA に4つの独立したバグ**が見つかり、すべて修正・デプロイした（第31〜34回）。
> **8 PR マージ・全て CI 緑・本番実測で検証済み。**

### AYANE 内側11ページ = 新デザイン本番反映（施主レビュー済み）

- 納品を **`PublicHtmlSanitizer` に実際に通して**検証（43パターン）。全11ページで要素数・img 数・
  data:URI 数が不変＝**無損失**。外部リソース依存ゼロ。守秘ルール・裏取り数字も機械チェック。
- 画像は 07-13 と**バイト同一**だったので、本番実績のある `redesign/opt/` を踏襲
  （ロゴ=PNG・ショット=WebP の data:URI）。
- **home は再投入せず**（納品版は 07-13 の生ソースで、統合リナが直した「大手フィンテック経由」の
  守秘違反が残っている。差分は組版のみ）。
- 投入は単一トランザクション＋DBバックアップ。手順書 `_work/ayane-site-build/`。
- ⚠️ **引き継ぎ書に無い12枚目 `/trust` が本番に存在**していた（07-14 追加）。DB を正とすること。

### 🔴 公開SSR/SPA のちらつき — **同じ症状に独立した原因が4つ**あった

施主が実機で3回「まだ出る」と指摘し、そのたびに別の原因が出た。**curl や DOM の一点観測では
1つも検出できず**、決め手は毎回**実ブラウザ**（最後は Playwright の動画をフレーム分解）。

| Issue | 原因 |
|---|---|
| **#879** | **SSR が `layout` を無視**。`bare` にも汎用 chrome（パンくず・`← ページ`・`<h2>content</h2>`）を出力。`PublicLayouts::resolve()` は実装もテストも存在したが**呼んでいるのは自身のテストだけ**だった＝配線漏れ。仕様書（docblock）自身が `bare: no header/footer, no theme` と書いており実装が仕様に違反 |
| **#881** | SPA が bootstrap から **permalink 解決を seed していない** → resolve 待ちで `RecordShellMessage`（テーマ chrome＋Loading…）を描画。bootstrap に `canonicalPath` を追加して seed |
| **#883** | ①1セグメント URL は SPA が**タイプ一覧**と解釈し `PublicBrowsePage` に入る。その型一覧クエリが seed と**キー不一致**（`{limit:20}` vs `{limit:100}`）②**blocks だけ seed 不能で毎回フェッチ**し `isLoading` を握っていた（サーバは型が宣言するデータ型しか読まない設計なのに SPA だけ無条件） |
| **#887** | **bootstrap の `entityTypes` が `{id,name,slug}` だけ**。SPA は hydrate して二度と取り直さないので **`defaultLayout`/`permalinkPattern` が永久に undefined** → `resolveLayout` が `standard` に落ちる。**DB で `default_layout=bare` に直しても SPA に伝わっていなかった** |

- **#887 は3度目の同型バグ**。`entity` payload には既に同じ教訓のコメントが2つある
  （#778 可視性フラグ / #816 canonical identity）。個別に気づいて足す運用が破綻しているため、
  **payload の形をテストでキー集合の厳密一致 pin** した。
- 併せて **layout 未確定の間はテーマ chrome を推測描画しない**ように（推測は bare サイトで必ず外れる）。

### #885 案A: bespoke ページが SPA 遷移になった

- **実測で判明**: ayane（bespoke）は遷移のたび**フルリロード**、aozora（テーマ）は**クライアント遷移**。
  原因は bespoke 機構の帰結で、`content` フィールドに React の `<Link>` を書けず生の `<a>` になるため。
  **これが毎回 SPA を再マウントさせ、上記4バグを毎回顕在化させる土台**でもあった。
- 公開シェルに**委譲クリックリスナ**（`resolveSpaLink` は純関数＋21テストで除外条件を固定）。
- **案B（`custom` レイアウト＋デザインのテーマ化）は #885 に open のまま**。chrome の重複
  （ナビ1項目の変更＝12ページ再生成）も同時に解決する本質策だが launch には間に合わない。

### その他の本番反映

- **#877/#878 タイプ一覧 SSR**: `/posts` 等が SPA 限定で title=「NeNe Records Admin」だった。
  **aozora の `work` 1,114件が初めてクロール可能に**。SPA の `usePublicBrowseEntityRecordsPage` と
  同条件（published のみ・PAGE_SIZE=20・id 降順）を意図的に写した。
- **#872/#874 OFL 表示義務**: subset の name テーブルが空・OFL.txt 無し・CSS も一言のみで
  **OFL 1.1 §2 の3経路すべてが空**だった。`vite.config.ts` から `assets/OFL-ZenKakuGothicNew.txt` を
  emit（既存の Apache `/assets` alias で配信＋ZIP へも自動同梱）＋ build-release.sh に fail-loud guard。
  ★実測: **`/*!` legal comment は Vite 8/rolldown の最小化で消える**のでライセンス表示に使えない。
  ★上流に RFN 宣言が無いので family 改名は不要。
- **#875/#876 公開ラベル導出**: `/company/ceo` のパンくず 60.5KB・JSON-LD 61.8KB が生HTML全文だった
  （#849/#853 の**公開側の双子**。あの2本は管理画面側のみ）。→ 実測 298.7KB→114.4KB / JSON-LD 0.4KB。

### 本番設定の是正（コード変更なし・即反映）

- `theme_overrides` の**陳腐化した色**（accent `#ff0000` / surface `#8b2323`）→ デザイントークン
  （`#D64525` / `#F6F3EC`）。bespoke はテーマを使わないので**背景色だけが漏れていた**。
- 型 `pages` の **`default_layout` を `standard`→`bare`**（全12レコードが bare なのに型既定が
  standard で、ロード中に standard chrome が描かれていた）。
- **未使用 `posts` 型を削除**（0件・参照ゼロを確認し、製品の `DeleteEntityTypeUseCase` 経由）。

### ⚠️ デプロイの前提（次回必読）

**本番の NENE2 は sibling ディレクトリ（`~/envs/suite-stg/records/NENE2`）で `@dev` 解決**。
07-15 時点で 7/8 相当（src 209ファイル）まで後れており、**そのまま main を載せると #869 の
`enableAuthorizationHeaderFallback` が古い NENE2 に当たり起動不能**だった（デプロイ前チェックで捕捉）。

- **CI は `git clone --depth=1` ＝ NENE2 main を使う**。`composer.json` も `"hideyukimori/nene2": "@dev"`。
  → **v1.11.0 は「リリース済みだが誰も検証していない組み合わせ」で、main が実証済みの組み合わせ**。
- `docker/php/Dockerfile.prod` は `additional_contexts: nene2` → `COPY --from=nene2 . /var/www/NENE2`。
  **`NENE2/` を先に rsync しないとビルドしても古いまま**。
- rsync `--delete` は NENE2 の `frontend/`（デモ用React）22件を消すだけで `src/` は無傷だが、**`--delete` は使わない**運用にした。

## 残（open）— **このリストが正**（2026-07-16 時点）

### AYANE 公開（07-19）まで

- **contact フォーム開通**（別リポ nene-contact）＝ **最後のブロッカー**。records 側の前提は解消済み
  （下記 #896 クローズ・確定 URL 3本を統合リナ経由で連携済み・contact は差替を確定扱いに格上げ）
- ~~**#896** `/downloads/*.pdf` 未配信~~ → **クローズ（07-16・受け入れ条件全達・本番は読み取り確認のみ無変更）**。
  実は **3PDF とも 07-12 01:35 に media アップロード済みだった**（org=ayane・media id 1〜3）。canonical =
  `https://ayane.nene-records.com/media/2026/07/<hash>.pdf`（本番実測 200/application/pdf・バイト一致）。
  contact の実測 URL とも突合一致（contact の「4本目」は `/privacy` の HTML ページで PDF ではない）。
  `/downloads/*.pdf` のソフト404 自体は残るが、参照 0 本・方針Aで不使用のため対応不要

### launch 後

- **#885 案B**（`custom` ＋ デザインのテーマ化）= chrome 重複の本質策
- **#873** org 単位のフォントアップロード（Font Library）。**AYANE 専用 subset が製品コアに焼き込まれ、
  base ZIP に 389KB 常時同梱＋全 org の公開サイトで @font-face 登録**されている構造問題の解決
- **#892** 公開一覧の textFields 取得窓（`{limit:100, offset:0}` が表示窓 id 降順20件を覆っていない）。
  #891 で出血は止血済みだが `meta_title` が空だと再発。SSR の `findByEntityIds($ids)` が正解の形
- **#883 の残**: bootstrap に `blocksFields` を載せる（blocks を持つ型では依然フェッチ）
- **#894 の割り切り**: org の override accent が読み込み中バーに届かない
  （`buildOverrideCss` が `.nene-public[data-theme=…]` スコープ）→ 組み込みテーマの accent が出る
- AYANE トップの「最近の動き」3カードが `/blog` と不整合（日付・タイトル違い／`NeNe invoice のデモ` は
  記事が存在せず行き止まり）。**リンクは3枚とも200**＝壊れてはいない。日付整合と同じく ayane.co.jp 移行時に
- #774 ニュースレター / #741 クローズ判定（AYANE Tier A 移送の実走待ち）

### 完了（このリストから外したもの・2026-07-16）

- ~~遷移中の ~75ms の空白~~ → **#894** で上端バー＋中央3点を出した（無描画は維持）
- ~~`/blog` の扱い~~ → **実体化済み**（entity 1150・施主裁定「一次的に固定ページ」）
- ~~`/for-accountants` フッターに13リンク~~ → **導線を全除去**（施主GO 07-16。トップの節も含む）

## 前回: 残フォローアップ一掃バッチ（2026-07-14・main 反映・本番デプロイ済み）

> repo-status で洗い出した「次の一手・リスク」のうち自走可能な全件を、並列サブエージェント＋指揮レビューで
> 一括出荷した。**8 PR すべて main マージ済み・CI 緑**（#837/#838/#839/#841/#842/#843/#846 ＋追加の #850）。
> ✅ **本番デプロイ済み（2026-07-14・施主 GO・第29回）** — runbook どおり DB バックアップ →
> rsync（dry-run deletions ゼロ確認）→ build --pull → up。検証: app (healthy)・apex/aozora/ayane 200・
> www→apex 301 維持・baked VERSION 0.5.2・エラーログ無し。migration 無し・NENE2 sibling 更新不要の回。

- **#849（PR #850）admin 一覧のレコードラベル爆発を修正**（バッチ後の施主報告で発覚）: title 無しレコード
  （bare bespoke ページ）で先頭フィールドの生 html 全文がタイトル描画され一覧が 41,121px に爆発 →
  非 title フォールバックをタグ除去＋120字キャップに正規化＋一覧タイトル 1 行 truncate ＋ body 200字キャップ。
  ローカル ayane 実データ＋headless Chromium で 41,121px→1,137px を実測確認。
- **#853（PR #854）ラベルで meta_title を導出抜粋より優先**（施主レビュー第2弾: 導出抜粋だと bespoke ページが
  全行同じ文字列で区別不能）: 優先順位= title フィールド → meta_title → 導出抜粋 → id。
  **第30回デプロイで本番反映済み（2026-07-14 夜・施主 GO）**。

- **#814（PR #837）メンテナンスモードの管理 UI トグル**: 一般設定に即保存スイッチ＋ON 時の注意表示
  （来訪者=メンテ表示／ログイン中=通常）。#813 のフォローアップ完了。
- **#836（PR #839）webhook secret を read で write-only 化**（#824 申し送り）: read から secret を完全除去し
  `has_secret` で代替・省略更新は既存値維持・編集フォームは新値入力のみ。secret の明示解除の導線は無し（必要時に別 issue）。
- **#845（PR #846）通知チャネル config の機微キーも write-only 化**（#836 と同型・レビュー時に新規発見）:
  slack/discord `webhook_url`・chatwork `api_token`・webhook `url`/`headers_json` を read から除去し
  config 内 `has_<key>` で代替。送信経路は repository の実値参照で不変（回帰テスト済み）。
- **#796（PR #838）移送カバレッジ拡充**: comments / webhooks / webhook_deliveries / notification_channels /
  user_profiles を export/import 対象化。**users は非移送**（パスワードハッシュを跨がせない）・user_profiles は
  email 突合で再アタッチ（不一致は skip 件数を import 結果に報告）・webhook secret は移送しない（NULL 挿入）。
- **#798（PR #842）メディア実ファイルの zip 同梱移送**: CLI 専用の zip 系統（`tools/export-org.php` /
  `tools/import-org.php`）で DB＋`var/media` 原本を単一 zip に。派生画像はオンデマンド再生成のため原本のみ。
  S3 ドライバは対象外を明示エラー化。zip-slip / パストラバーサルはガード＋テスト済み。
  `docs/install-tier-a.md` の移送節を zip 手順に更新（rsync/FTP は代替として残置）。
- **#802（PR #843）trusted-embed Phase 2 = 一級 widget プリミティブ** → **#802 全 Phase 完了・クローズ**:
  widget 型 `trusted-embed`（origin/src/SRI 必須/data-* のみ）を保存側検証＋SSR（noscript シェル）＋SPA parity で描画。
  allowlist 空なら公開ページ byte-for-byte 不変を回帰 pin。申し送り2点（SRI runbook・自己資産オリジンのみ信頼）は
  `docs/security/trusted-embed.md` に収録。Phase 3（media 永続化）は #807 で完了済みと判定。
  admin UI（allowlist 編集・data-* エディタ）＋staging 実測は **#844** へ。
- **#709（PR #841）配布 ZIP のフォント分離**: base **66MB→14MB 実測**（フォント 53MB→2.0MB・非フォント下限 ~12MB）。
  keep=管理 UI ＋既定テーマ consumer ＋ #818 公開フォント。残り 482 woff2 は `nene-records-fonts-<version>.zip`（52MB）
  に分離（**次リリースから Release に両 zip＋sha256 を添付**）。未導入時はフォントピッカーに注記＋ヒント
  （docroot の `font-pack.json` マーカーで判定・Docker/本番は無影響）。HTTP 自動取得は **#840** へ。
- **本番実測**: #833/#835（www→apex 301）が **07-13 深夜ビルドで本番反映済み**なのを curl 実測で確認
  （www が 301 → apex・SPA シェルに飲まれない）。
- **#741**: 残ギャップ 4 件（#795/#796/#797/#798）が全てマージ済みになったため進捗コメントを投稿。
  クローズは受入基準の「2 インスタンス実走」＝AYANE Tier A 移送の実走後に判定。

### 残フォローアップ（open・2026-07-14 時点）

- **#844** trusted-embed の admin UI 拡充＋staging 実ブラウザ実測（#802 引き継ぎ）。
- **#840** フォントパックの管理画面/インストーラからの HTTP 自動取得（#709 引き継ぎ）。
- **#774** ニュースレター購読フォームの実体。
- **#741** クローズ判定（AYANE 移送の 2 インスタンス実走が e2e 検証を兼ねる）。
- ~~本節の本番デプロイ~~ ✅ 2026-07-14 反映済み（上記）。**次リリース zip はフォント 2 分割**（base＋fonts・#709/#841）に
  なる点だけ Release 作業時に注意。

## 前回: 移送経路の仕上げ＋公開サイト堅牢化バッチ（2026-07-12〜13・main 反映）

> #741 Phase 1/2 のマージ後、移送の取りこぼしを埋めつつ公開サイト側の堅牢化を連続出荷した。
> すべて main 反映済み・CI 緑。**本番デプロイは施主 GO（2026-07-13）** — 下記「本番デプロイ」参照。

- **移送（#741 系）の追い込み**:
  - **#795（PR #812）ブロック本文・home_hero の media 参照書き換え**: export/import 時に
    ブロック本文 JSON・`home_hero` 等に埋め込まれた media 参照を mediaMap で再マップ（Phase 2 の
    設計残＝「JSON 内 media URL 未書き換え」を解消）。本文 verbatim 取り込みの穴を塞いだ。
  - **#801（PR #806）front_page エンティティ id 再マップ**: 移送時に org 設定 `front_page` が
    指すエンティティ id を entityMap で再マップ（移送先で別 id になってもトップ固定が壊れない）。
- **公開サイト堅牢化**:
  - **#797（PR #805）superadmin コンソールを superadmin 専用に閉じる**: 認証さえ通れば非 superadmin でも
    横断コンソール／org export・import API に到達できた緩さ（#741 設計所見の別 issue 化分）を capability で封鎖。
  - **#802（PR #803）信頼済み埋め込みオリジンの CSP allowlist（Phase 1）**: 公開ページ CSP に
    per-org の trusted-embed allowlist を導入（査読承認＋施主 GO 07-12・board due 07-16・07-25 公開の依存）。
    残 = Phase 2（widget 一級 trusted-embed プリミティブ）／Phase 3（media 同乗）＝#802 継続。
  - **#799（PR #800）bare レイアウトで単一 html 本文を素直に描画**: 1 html フィールドの bespoke ページ
    （AYANE 型）を inline＋bare layout で忠実描画。
  - **#813（PR #815）org 単位の公開メンテナンスモード**: org ごとに公開ページをメンテ表示へ。
    管理 UI トグル（#814）はフォローアップ（open）。
  - **#816（PR #817）SSR bootstrap の permalink/slug 欠落で直リンク `/pages/{id}` が落ちる不具合を修正**。
  - **#818（PR #819）公開サイトのフォント self-host**: Zen Kaku Gothic New / IBM Plex Mono を自前配信
    （外部 CDN 依存を排し CSP と整合）。
- **リリース／運用**:
  - **#807（PR #809）本番 compose に `var/media` 永続ボリュームを追加**（再ビルドでメディア消失を防止）。
  - **#808（PR #810）`VERSION` を 0.5.2 にバンプ**（版の単一ソースを公開最新 0.5.2 に一致・`/machine/health` も同値）。

### 本番デプロイ（2026-07-13・施主 GO・✅ 反映済み）

- **判定: GO（施主承認 2026-07-13）。反映は既に完了済み** — 本節の main 差分（#793/#794 移送 Phase 1/2・
  #795・#797・#799・#801・#802・#807・#808・#813・#816・#818）は **2026-07-13 05:28 JST のビルドで本番反映済み**
  （最終コミット #819 の 3 分後にイメージ build＝HEAD 相当）。当日 15:46 に検証で確認:
  - `records-app` イメージ build 時刻 = 2026-07-13 05:28、baked `VERSION`=0.5.2。
  - phinx migration は `20260720000000`（メンテモード #813）まで **全 up・pending 0**。
  - `var/media` 永続ボリューム（#807）は `records-media-data` として稼働・既存メディア保持（消失なし）。
  - health 200: apex `nene-records.com` / `aozora.nene-records.com` / `ayane.nene-records.com`。
  - SSR 実証: `/work/aozora-000148-60818` が SSR（title/OG/canonical/JSON-LD BlogPosting）で描画、
    bootstrap に `slug` 同梱（#816 の直リンク修正が live）、self-host フォント
    `zen-kaku-gothic-new-*.woff2`（#818）が同一オリジン 200 で配信。
- ⚠️ 記録の齟齬: 本 doc は以前「07-09 が最終デプロイ」と記していたが、実際は **07-12〜13 に再デプロイ済み**
  だった（current.md の遅延が原因）。以後の「未デプロイ」判断は git／サーバ実測で確認する（memory `verify-state-via-git-not-docs`）。
- server 側 authoritative compose は `~/envs/suite-stg/records/compose.yaml`（repo `compose.prod.yaml` とは別・
  `records-media-data` volume も反映済み）。再デプロイ手順は runbook（private `nene-records-marketing/ops-inbox/records/`）。

### 残フォローアップ（→ 2026-07-14 バッチで #774 以外すべて消化・上の最新節参照）

- ~~**#814** メンテナンスモードの管理 UI トグル~~ ✅ PR #837
- ~~**#802** trusted-embed Phase 2／Phase 3~~ ✅ PR #843（クローズ・admin UI は #844）
- ~~**#796 / #798** 移送カバレッジ拡充~~ ✅ PR #838 / #842
- **#774** ニュースレター購読フォームの実体（未着手のまま）、~~**#709** 配布 ZIP フォントの on-demand 化~~ ✅ PR #841（HTTP 自動取得は #840）

## 進行中: #741 移送経路 Phase 1（SaaS org → Tier A・ブランチ feat/741-org-transport-phase1）

指示書 `_work/handoff-records-741-export-import-2026-07-11-work-order.md`・報告
`_work/handoff-records-741-export-import-2026-07-11-report.md`。

- **① 衝突マージ + id リマップ**: `PdoOrgImportRepository` を、entity_types（org+slug）/
  field_defs（org+entity_type+field_key）/ setting_defs・setting_values（org+setting_key）で
  既存シード行にマージ（移送元優先の update）・id リマップ表を後続 FK に波及。
- **② トランザクション化**: import 全体を `DatabaseTransactionManagerInterface::transactional`
  で 1 トランザクション化。途中失敗で org 無傷を SQLite テストで実証。
- **③ 列脱落解消**: entities.permalink/menu_order/layout/show_comments/show_related・
  navigation_items.menu_id・media.alt_text/width/height/storage_key・entity_types.default_layout/
  display_order・field_defs.region/display_order を INSERT に追加。SQLite ラウンドトリップ
  テストで新列の往復を検証（列脱落再発を検知）。schema fixture `media.sql` も現行列に追随。
- **⑥ Tier A import 手段**: `tools/import-org.php`（CLI・`--file`/`--org`）。install.php/
  webhook-worker.php と同じ RuntimeContainer 経由。stop-gate（auth/tenancy 不変条件）不抵触を
  確認済み（全行 organization_id 刻印・運用者ツール）。build-release.sh の同梱 allowlist に追加。
- 検収: ローカル MySQL 実 DB で org1 export(160KB) → fresh org へ CLI import 成功。
  entity_types/setting_defs/field_defs が重複せずマージ・custom permalink 往復を確認。
- **Phase 1 は merge 済み（PR #793・main 19eb9aa・CI 緑・本番デプロイは未実施）**。

### Phase 2（ブランチ feat/741-org-transport-phase2・④⑤）

- **④ テーブル拡充＋ID リマップ**: menus / widgets / themes / blocks_fields / entity_relations /
  url_redirects を export・import 両方に追加。navigation_items.menu_id を menuMap で実リマップ、
  menu ウィジェットの settings.menuId をリマップ、entity_relations の source/target を entityMap で
  リマップ、blocks_fields の entity_id をリマップ、setting_values の logo_media_id を mediaMap で
  リマップ。menus/themes は org+slug/theme_key でマージ。
- **⑤ メディア実ファイル**: `docs/install-tier-a.md` に「別インスタンスからの移送」節を追加
  （export → var/media rsync/FTP → CLI import の手順）。
- 検収: 実 MySQL で org1 に menu/menu-widget/theme/url_redirect を仕込み → fresh org へ round-trip、
  navigation_items.menu_id・widget settings.menuId が移送先 menu id に再マップされることを確認。
- **設計所見（残・要 issue 化）**: ブロック本文・一部設定（home_hero/footer_config 等）の JSON に
  埋め込まれた media URL・entity 参照は書き換えていない（body は verbatim 取り込みで本文は保全・
  相対 URL は同一パス配置で解決）。comments/webhooks/notification_channels/user_profiles は
  今回スコープ外。superadmin export 系ルートの capability 未設定（認証さえ通れば非 superadmin でも
  HTTP import 可）の緩さも別 issue 化推奨。

## 直近: AYANE Tier A 設置リハーサル＋移送経路の実証（2026-07-09）

## 直近: AYANE Tier A 設置リハーサル＋移送経路の実証（2026-07-09）

> 文脈: AYANE サイトリニューアルは records で構築（施主決定 07-09）。制作は
> `ayane.nene-records.com`、最終設置は **Tier A zip（独自ドメイン）**、差し替えは DNS 切替。
> 指示書は `_work/handoff-records-ayane-tier-a-2026-07-09-work-order.md`、
> AYANE 実設置の分担表は `_work/ayane-tier-a-install-runbook.md`（実サーバ情報を含むため公開リポ外）。

- **Tier A 設置リハーサル（v0.5.1 zip・共有ホスティング相当ハーネス＝php:8.4-apache 公式・
  Alias 無し・docroot=public_html）**: ルート設置／`/blog` サブディレクトリ設置（APP_BASE_PATH 自動導出）
  とも、要件→DB/tenant→migrate 77本→管理者→完了→再訪403 まで完走。公開 SSR（canonical/OG の
  base-path 前置）・SPA 管理画面（`<base href>`）・sitemap/robots・theme-thumbnails・メディア＋
  オンデマンド派生・cron・設定17定義シード すべて動作確認。
- **#737 修正（P0・merged PR #738）**: GD が AVIF 無しビルドの環境（共有ホスティングで実在・検証
  ハーネス自体が該当）で、Chrome/Edge の `Accept: image/avif` により**派生画像が全て 500**。
  形式交渉を encoder 能力込みに変更（avif→webp→ソース形式フォールバック）。Docker 本番は
  `--with-avif` ビルドのため挙動不変。
- **#739 修正（merged PR #740）**: superadmin org export/import API（M8 #215）が
  `getAttribute('id')` で {id} を読めず**常に 404**（NENE2 Router は PARAMETERS_ATTRIBUTE 配列でしか
  渡さない）。実 Router 配線の HTTP テストを新設（OrgExport は従来テスト皆無）。
- **#741 起票（P0・今週レーン）: 移送経路（SaaS org → Tier A）は現状「不通」を実証** —
  2インスタンス実走で ① fresh 設置へ import すると seed 済み posts/pages と
  `uq_entity_types_org_slug` 衝突で**1行目から 500** ② import 非トランザクション
  ③ M8 以降の列が import で脱落（entities.permalink/menu_order/layout・navigation_items.menu_id・
  media.alt_text/width/height/storage_key）④ menus/widgets/themes/blocks_fields/entity_relations/
  url_redirects がテーブルごと範囲外 ⑤ メディア実ファイルは別送 ⑥ Tier A に superadmin が
  存在せず import 実行手段なし。受入基準と設計案は #741 参照。
- **v0.5.2 zip: ✅ GitHub Release 公開済み（2026-07-09・施主 GO・Latest）**: #737/#739/#710
  （サイト名→site_name 反映・SSR title「— AYANE」確認）/#716 を収録。fresh ハーネスで
  設置→AVIF 交渉 200 webp→export 200 をスモーク済み。66MB・NENE2 v1.8.2 同梱・
  sha256 `ad469d1d…`（DL 再検証で一致）。**AYANE 実設置は v0.5.2 以降を使うこと**
  （v0.5.1 以前は AVIF 500 を含む）。
- **本番デプロイ: ✅ 完了（2026-07-09・施主 GO）**: 07-04 時点（`a1dba34`）から main 18 コミット
  （#715〜#736: JWT フェイルクローズ #725・SSRF ガード #732・設定 UI #722・画像ノブ #723 等
  ＋本日 #738/#740/#743/#745）を反映。**#745 で PHP ベースイメージを 8.4.23 にピン**
  （CVE-2026-12184 FPM DoS / CVE-2026-14355・横断 board 課題 #39 の records 分）し、
  `--pull` 再ビルドでコンテナ `php -v`=8.4.23 を確認。手順は runbook どおり
  （DB バックアップ `backups/nene_records_pre_deploy_2026-07-09.sql` → NENE2 sibling rsync
  （v1.8.2）→ 本体 rsync（commercial/composer.local.json 除外・`--exclude=.claude` を追加し
  サーバ側の残骸も一掃）→ build --pull → up → phinx migrate（`20260716000000` 適用））。
  検証: apex/aozora/ayane の health 200・SSR レコードページ描画・新 SPA バンドル配信 200。
  これで移送元 `ayane.nene-records.com` の export API（#739 修正）も本番で使用可能。
- フォローアップ判定: **#709 フォント on-demand は後回し**（設置は FTP/SFTP 前提で 66MB は
  アップロード制限に当たらない）。#710 は完了済み（v0.5.2 に収録）。

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
