# 共有ホスティング設置ガイド（Tier A・zip インストーラ）

NeNe Records を「zip をアップロードして install ページを開くだけ」で共有ホスティング
（HETEML・ロリポップ等の Apache 系）に設置する手順。Docker / VPS デプロイは
`docs/deployment.md` を参照。

## 前提条件

- **PHP 8.4.1 以上**（ホスティングの PHP バージョン設定で選択）
- MySQL データベース 1 つ（ホスティングの管理画面で作成し、DB 名・ユーザー・パスワードを控える）
- 必須 PHP 拡張: pdo / pdo_mysql / mbstring / json / openssl / ctype / fileinfo / dom / libxml / gd
  （インストーラが自動チェックする。事前に確かめたい場合は `tools/hosting-precheck.php` を
  単体でアップロードしてブラウザで開くと READY / NOT READY を判定する）

## 設置手順

1. **リリース ZIP を作る**（配布物を受け取っている場合はスキップ）:

   ```bash
   bash tools/build-release.sh <version>
   # → dist/nene-records-<version>.zip        本体（Packagist の NENE2 を解決した自己完結 zip・約11MB）
   # → dist/nene-records-fonts-<version>.zip   フォントパック（オンデマンドの追加フォント・約55MB）
   ```

   本体 ZIP は「設置直後から見た目が破綻しない最小フォントセット」（管理画面フォント＋
   既定テーマ＋日本語見出し）だけを同梱し、テーマのフォントピッカーで選べる残りの
   フォント（CJK の Noto Sans JP/SC・Shippori Mincho ほか）は **フォントパック** に
   分離している（#709）。GitHub Release には **本体・フォントパック両方の ZIP と各
   `.sha256`** を添付する（Latest・target=main）。フォントパックは任意導入なので、まず
   本体だけで設置を完了できる。詳細は後述の「フォントパック（任意）」を参照。

2. **アップロード**: 本体 ZIP を展開した中身一式をサーバーへアップロードする
   （例: `~/web/nene-records/` 配下に `src/ vendor/ public_html/ …` が並ぶ形）。

3. **公開フォルダを `public_html/` に向ける**: ホスティングの管理画面で、サイトの
   ドキュメントルート（公開フォルダ）をアップロード先の `public_html` に設定する。
   `.env` や `src/` は公開フォルダの外に置かれるのがこの構成の狙い。

4. **インストーラを開く**: ブラウザで `https://あなたのドメイン/install/` を開き、
   ウィザードに従う。**アップロード後は速やかに設置を完了すること**（未設置のウィザードが
   開いている間は URL を知っていれば誰でも設置できるため。設置完了後は自動で遮断される）:
   - **ステップ1 — サーバー要件**: 全項目 OK になるまで解消して再チェック。
   - **ステップ2 — データベースと利用形態**: DB 接続情報を入力。`.env` の書き込みと
     migration（スキーマ初期化）まで自動で行う。利用形態は通常「単一サイト（標準）」の
     まま（マルチテナントは詳細設定から）。
   - **ステップ3 — 管理者アカウント**: サイト名・メール・パスワード（8文字以上）。
   - **完了**: 表示されたリンクから管理画面にログイン。

5. **後片付け（推奨）**: サーバー上の `public_html/install/` ディレクトリを削除する。
   削除しなくても `var/.installed` マーカーと DB 検査により再実行は 403 で遮断される。

6. **PHP タイムゾーンを UTC に固定する（推奨）**: 共有ホスティングは PHP の
   `date.timezone` がローカル時（例: HETEML は `Asia/Tokyo`）のことがあり、そのままだと
   JSON-LD 等の日時が `+09:00` で直列化されて他インスタンス（SaaS は `+00:00`）と実時刻が
   9 時間ズレる（#952 実測）。`public_html/.user.ini` に 1 行置けば解消する:

   ```ini
   date.timezone=UTC
   ```

## フォントパック（任意）

本体 ZIP は配布サイズを抑えるため（66MB → 約11MB・#709）、フォントを最小セットだけ
同梱している。**同梱済み**なのは次の用途:

- 管理画面の UI フォント（Inter / Saira Semi Condensed / Space Grotesk / JetBrains Mono）
- 既定の公開テーマ「consumer」の見出しフォント（Bricolage Grotesque）
- 公開サイトの日本語見出し・本文（Zen Kaku Gothic New サブセット）と mono ラベル（IBM Plex Mono）

これ以外の、テーマのフォントピッカーで選べるフォント（Playfair Display・Oswald・
Source Serif 4・Roboto、および CJK 全域の Noto Sans JP/SC・Shippori Mincho など）は
**フォントパック** に分離している。フォントパック未導入でも、選ばれたフォントは CSS の
フォールバック（システムフォント）で描画されるため見た目は破綻しない。管理画面
「外観 > カスタマイズ」のフォント選択では、未導入フォントに「（フォントパック）」の
注記が付き、上部に導入を促すヒントが表示される。

### フォントパックの導入（FTP）

shell の無い共有ホスティングでも導入できる手動手順（正）:

1. `dist/nene-records-fonts-<version>.zip` をローカルで展開する。
2. 中身の `public_html/assets/` 配下の `*.woff2` / `*.woff` を、本体を設置したのと
   **同じ階層**（`public_html/` がある場所）へ、フォルダ構成ごと **上書き** アップロード
   する。既存ファイルとは衝突しない（ハッシュ名なので純粋に増えるだけ）。
3. 反映確認は管理画面「外観 > カスタマイズ」のフォント選択で、追加フォントの
   「（フォントパック）」注記が消えることで分かる。

> **バージョン整合**: 必ず本体 ZIP と同じ `<version>` のフォントパックを使うこと。
> Vite の出力ファイル名は版ごとにハッシュが変わるため、版がズレると CSS の参照先と
> 一致せず反映されない。

> **将来**: 管理画面／インストーラからフォントパックを HTTP 取得して自動展開する導線は
> follow-up（PHP `ZipArchive` ＋ `allow_url_fopen`/curl 検出、失敗時は上記 FTP へ誘導）
> として検討中。現時点では上記の FTP 手動導入が唯一の手段。

## サブディレクトリ設置

`https://example.com/blog/` のようなパス配下でも設置できる。インストーラが URL から
`APP_BASE_PATH` を自動導出して `.env` に書き込み、公開 URL 生成とルーティング
（front controller の prefix strip）が対で成立する。設置手順は同じ —
`https://example.com/blog/install/` を開くだけ。

## cron の設定（予定公開・Webhook 配信）

予定公開（`scheduled_at`）と Webhook 配信は毎分の cron で駆動する。ホスティングの
cron 設定に以下の 2 本を登録する（URL は自サイトに読み替え）:

```
* * * * * curl -fsS -X POST https://あなたのドメイン/api/v1/entities/process-scheduled >/dev/null
* * * * * curl -fsS -X POST https://あなたのドメイン/api/v1/webhooks/process-deliveries >/dev/null
```

cron を設定しない場合、即時公開・閲覧などの基本機能は動くが、予定公開と Webhook は
実行されない。

## 別インスタンスからの移送（SaaS org → Tier A 設置）（#741）

SaaS 側（例 `ayane.nene-records.com`）で制作したサイトを、この Tier A 設置へ丸ごと移す手順。
コンテンツ（entity/フィールド/ブロック本文/リレーション）・タグ・ナビ/メニュー・ウィジェット・
テーマ・設定（テーマ選択/レイアウト/フロントページ/カスタム permalink 等）・メディア（DB 行＋
実ファイル）・301 リダイレクト・コメント・Webhook・通知チャンネル・ユーザプロフィールが移送対象。

### 推奨: zip 一括移送（DB＋メディア実ファイルを 1 ファイルで）（#798）

`tools/export-org.php` は DB payload（`export.json`）とメディア原本（`var/media/` を
`storage_key` の相対パスのまま同梱）を含む単一 zip を書き出す。`tools/import-org.php` が
その zip を展開し、原本を `var/media/` へ配置してから DB 行を取り込むので、画像入りサイトが
1 操作で移送先に表示される。派生画像（サムネイル等）は移送先で **オンデマンド再生成** される
ため同梱しない（原本のみ）。**ローカルディスク保存（`MEDIA_STORAGE_DRIVER=local`）専用**。

```
# 1) 移送元で zip を書き出す（SSH シェルから）
php tools/export-org.php --org=<id|slug> --out=org-export.zip
#   docker: docker compose exec -T app php tools/export-org.php --org=aozora --out=/tmp/aozora.zip

# 2) zip を移送先へ転送する（scp/rsync/FTP どれでも 1 ファイル）
scp org-export.zip user@あなたのホスト:/path/to/app/

# 3) 移送先で取り込む（DB 行＋メディア原本が同時に入る）
php tools/import-org.php --file=org-export.zip --org=<id|slug>
```

### 代替: JSON export ＋ メディア実ファイル別送（rsync/FTP）

zip が使えない場合（S3 ストレージ利用時・巨大メディアを個別転送したい場合など）は、従来どおり
JSON export とメディアを別々に転送する。

**1. 移送元で JSON を書き出す** — superadmin で org export API を叩く（media は DB 行のみ）:

```
curl -fsS -H "Authorization: Bearer <superadmin-token>" \
  https://ayane.nene-records.com/api/v1/superadmin/organizations/<org-id>/export \
  -o org-export.json
```

**2. メディア実ファイルを転送する** — 移送元の `var/media/` を、この設置の `var/media/` へ
丸ごとコピーする（相対パス＝`storage_key` を保つこと。URL も相対で解決されるためブロック本文中の
画像もそのまま表示される）:

```
# SSH がある場合（HETEML 等）
rsync -avz ./var/media/ user@あなたのホスト:/path/to/app/var/media/

# FTP/SFTP しか無い場合: var/media/ 配下を同じ階層のままアップロード
```

**3. この設置で JSON を取り込む（CLI）** — export/import API は superadmin 専用で Tier A の
installer は org admin しか作らないため、取り込みは同梱の CLI ツールで行う:

```
php tools/import-org.php --file=org-export.json --org=<id|slug>
# 例: php tools/import-org.php --file=org-export.json --org=default
```

### 取り込みの挙動（zip / JSON 共通）

取り込みは 1 トランザクションで走り、失敗時は org 無傷（部分取り込みなし）。設置時に
シード済みのコンテンツタイプ（posts/pages）・設定定義は **マージ**される（重複しない・
移送元の値で更新）。シード残骸も自動で照合される（#952）:

- マージされた型の **フィールド定義は移送元の集合に揃う**（移送元に無いシード defs
  （title/body）は論理削除される。残すと全公開ページに空の「title / —」スタブが出る）。
- 移送元に無いシード型（posts/pages）は、**どのエンティティ・リレーション定義からも
  参照されていない場合に限り**削除される（pinned のまま残ると公開 bootstrap に露出するため）。

**取り込み後の検証は SSR の curl だけでは不十分**。SPA hydration 後にだけ出る崩れがある
（#952 の「title / —」スタブは SSR に出ず curl では不可視だった）ので、実ブラウザで数ページ
確認するか、移送元・移送先の公開ページを URL 正規化して diff する（AYANE 本走の受入は
「正規化 diff 6 ページ差分ゼロ」で判定した）。

- **Webhook の secret は移送されない**（移送先で再設定する。`webhooks` / `webhook_deliveries`
  の secret はエクスポートに含めない）。
- **ユーザ本体（パスワードハッシュ・ロール）は移送されない**。`user_profiles` は移送先に
  同一 email のユーザが居れば再アタッチ、居なければ skip し件数を報告する。
- **S3 ストレージ（`MEDIA_STORAGE_DRIVER=s3`）では zip 移送は使えない**（原本がバケットにあり
  `var/media/` に無いため、export/import 双方が明示エラーで停止する）。バケットを別途複製し、
  JSON export を使うこと。

> **注意（現行の制限）**: id 参照の再マップは `logo_media_id` / `front_page`（#801）/
> `navigation_items.menu_id`・ウィジェットの `menuId`、およびブロック本文・`home_hero` に
> 埋め込まれた media 参照（mediaId＋URL の相対化・#795）まで対応済み。ただし **テキスト系
> フィールド値（html/markdown 等）の本文中に絶対 URL を直書きしている場合**は書き換えられない
> ため、ドメインの読み替えが要る（内部リンクを相対 URL で書いていれば解決する）。

## 既知の制約（v1）

- **メール送信は既定で無効**（`MAIL_DSN=null://null`）。招待メール等を使う場合は
  `.env` の `MAIL_DSN` をトランザクショナルメールプロバイダ（Resend / SES 等）に
  設定し、送信ドメインを検証（SPF/DKIM）する。`.env.example` に例がある。
- **ZIP は約 66MB**（うちフォントが 55MB — テーマのフォントピッカー用）。
  アップロードは FTP/SFTP 推奨。フォントのオンデマンド配信化は今後の課題。
- **アップデート機構は未提供**。当面の更新は「新しい ZIP で上書き → ブラウザで
  どこかのページを開く（migration は `composer migrations:migrate` 相当が必要なため、
  更新手順は今後のアップデート機構で提供予定）」。
- メディアは `var/media`（ローカルディスク）保存。S3 互換ストレージは `.env` で
  切り替え可能（`.env.example` の MEDIA_* を参照）。
