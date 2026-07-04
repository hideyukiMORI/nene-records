<!--
Qiita title: 型付きCMSで業務データと公開ページを管理する — NeNe Records を Docker で試す
Qiita tags: Docker, OSS, CMS, PHP, OpenAPI
related_issue: #684
-->

## はじめに

CMS というと、まず WordPress を思い浮かべる人は多いと思います。

WordPress はとても便利です。ブログ、固定ページ、メディア、テーマ、プラグインが揃っていて、小さなサイトから大きなサイトまで幅広く使えます。

一方で、業務データや社内向けの情報管理まで WordPress で扱おうとすると、`postmeta` に文字列として値を詰め込む形になりがちです。プラグインごとの独自仕様に寄り、API / 型 / 権限 / AI 連携の境界も見えにくくなることがあります。

**[NeNe Records](https://github.com/hideyukiMORI/nene-records)** は、NENE2 上で動く API-first な型付きエンティティ / CMS プラットフォームです。

エンティティタイプとフィールド定義を管理画面から作り、レコードを JSON API で保存し、公開ページとして配信し、OpenAPI / MCP 経由で AI ツールからも同じ API 境界を触れるようにすることを目指しています。

基盤には、API-first / OpenAPI / MCP 対応を前提にした軽量 PHP フレームワーク **[NENE2](https://github.com/hideyukiMORI/NENE2)** を使っています。

> **リリース状況（2026年6月時点）** — NeNe Records は OSS として開発中ですが、管理画面、型付きフィールド、公開ページ、SEO/SSR、WordPress WXR インポート、マルチテナント基盤など主要機能はかなり入っています。一方で、設定 UI の磨き込み、2FA / 権限細分化、共有ホスティング向け ZIP 配布などは継続開発中です。この記事は Docker での試用・評価向けです。MIT ライセンスですが **無保証** です。

> **誰向けの記事か** — WordPress 以外の軽量 CMS を試したい人、業務データを型付きで管理したい人、OpenAPI / MCP を前提にした CMS 的なアプリを見たいエンジニア向けです。

---

## NeNe Records でできること（この記事の範囲）

| 項目 | 内容 |
| --- | --- |
| 型付きデータ | Entity Type / Field Definition / Record で業務データを定義 |
| 管理画面 | React 管理 UI でコンテンツタイプ、レコード、メディア、ユーザーを管理 |
| 公開ページ | 実 permalink、SSR head、OGP、JSON-LD、sitemap、robots.txt |
| WordPress 移行 | WXR インポート、メディア取込、301 リダイレクト、SEO メタ取込 |
| API | OpenAPI 3.1 で JSON API 契約を管理 |
| MCP | OpenAPI に沿った MCP tool catalog を用意 |
| マルチテナント | organization スコープ、JWT、superadmin / admin / editor |

**しないこと** — WordPress プラグイン互換、WordPress テーマ互換、Elementor 的な任意 JS ページビルダー、AI による DB 直アクセスは扱いません。NeNe Records は WordPress 互換プロダクトではなく、NENE2 上の型付き CMS / 業務データ管理プロダクトです。

---

## 1. Docker で起動する

NeNe Records は NENE2 を sibling checkout として参照します。

そのため、まず `NENE2` と `nene-records` を同じ階層に clone します。

```bash
git clone https://github.com/hideyukiMORI/NENE2.git
git clone https://github.com/hideyukiMORI/nene-records.git
cd nene-records
cp .env.example .env

# .env を Docker の MySQL 用に切り替える（既定は host PHPUnit 用の SQLite）
#   DB_ADAPTER=mysql
#   DB_HOST=mysql
#   DB_NAME=nene_records
#   DB_USER=nene_records
#   DB_PASSWORD=nene_records
# （.env.example のコメントにある値と同じ。詳細は docs/development/docker.md）

docker compose up --build -d
```

ヘルスチェック:

```bash
curl http://localhost:18082/health
```

ローカルの主な URL:

| 用途 | URL |
| --- | --- |
| API | http://localhost:18082 |
| health | http://localhost:18082/health |
| OpenAPI | http://localhost:18082/openapi.php |
| 管理 UI（Vite dev） | http://localhost:18084 |
| phpMyAdmin | http://localhost:18083 |
| Mailpit | http://localhost:18026 |

初回に SQLite のまま起動してしまった場合は、DB 設定を変える前に `docker compose down -v` で MySQL の volume を作り直してください（空パスワードで初期化された volume が残ると Access denied になります）。

管理 UI は Compose には含めず、別ターミナルで Vite を起動します。

```bash
npm ci --prefix frontend   # 初回のみ：管理UIの依存をインストール
npm run dev --prefix frontend
```

<!--
スクリーンショット 1（必須）:
掲載場所: 「管理 UI は Compose には含めず、別ターミナルで Vite を起動します。」の直後。
画面: ターミナルで `docker compose up --build -d` と `/health` が通っている画面。
目的: Docker でローカル検証できることを最初に見せる。
写す要素: `curl http://localhost:18082/health` の成功、可能なら `docker compose ps` の app/mysql 起動状態。
-->

---

## 2. 初回セットアップ

NeNe Records には、以前存在した開発用のデフォルトユーザーは残っていません。

初回は installer で organization と admin ユーザーを作成します。

```bash
docker compose exec -T \
  -e NENE_INSTALL_ADMIN_EMAIL=admin@example.com \
  -e NENE_INSTALL_ADMIN_PASSWORD='change-me' \
  app composer app:install
```

`docker compose exec` はホストの環境変数を渡さないので、必ず `-e` で指定してください。付けないと「NENE_INSTALL_ADMIN_EMAIL and NENE_INSTALL_ADMIN_PASSWORD are required」で止まります。

このコマンドは冪等です。再実行しても、既存 organization / admin がある場合は安全に扱われます。

ログイン後、`/admin` からエンティティタイプ、レコード、メディア、設定などを管理できます。

<!--
スクリーンショット 2（必須）:
掲載場所: 「ログイン後、`/admin` から...管理できます。」の直後。
画面: ログイン後の管理ダッシュボード、または admin shell。
目的: WordPress 風の管理入口ではなく、NeNe Records の管理コンソールがあることを見せる。
写す要素: 左ナビ、ダッシュボード、公開数/アクセスなどの概況。
注意: 実メールアドレスや本番ドメインの管理情報が写る場合はマスクする。
-->

---

## 3. 型付き CMS としての基本構造

NeNe Records の中心は、次の3つです。

| 概念 | 内容 |
| --- | --- |
| Entity Type | `posts`、`pages`、`products` など、レコードの種類 |
| Field Definition | title、body、price、published_at など、型付きの項目定義 |
| Record | 実際のデータ。draft / published / archived / scheduled などの状態を持つ |

WordPress の `postmeta` は柔軟ですが、値の型や検証はプラグインや実装に寄りがちです。

NeNe Records では、フィールド定義を API 側の schema registry として持ち、書き込み時に API が検証します。

主なフィールド型:

- text
- markdown
- html
- blocks
- int
- enum
- bool
- datetime
- image
- file
- relation

この「管理画面で柔軟に作れるが、保存時は型で守る」という方針が、NeNe Records のいちばん大事なところです。

<!--
スクリーンショット 3（必須）:
掲載場所: 「この『管理画面で柔軟に作れるが...』の段落直後。
画面: Entity Type / Field Definition の編集画面。
目的: 「型付きCMS」であることを視覚的に伝える。
写す要素: フィールド名、型、必須設定、並び順。
おすすめ: `posts` や `pages` のような分かりやすい entity type を使う。
-->

---

## 4. レコードを作成する

エンティティタイプを作ると、そのタイプに対応したレコード管理画面が使えます。

たとえば `posts` なら、タイトル、本文、公開状態、タグ、SEO 情報などを持ったレコードを作れます。

NeNe Records は、単なる Headless CMS だけではなく、公開ページを持つ CMS としても育っています。

最近の実装では、Markdown や HTML だけでなく、`blocks` フィールドによるリッチページ編集、ライブプレビュー、レイアウト用ブロックも追加されています。

<!--
スクリーンショット 4（必須）:
掲載場所: 「ライブプレビュー、レイアウト用ブロックも追加されています。」の直後。
画面: レコード編集画面。
目的: 実際にコンテンツや業務データを入力する画面を見せる。
写す要素: title/body/blocks などの入力、公開状態、保存ボタン。
おすすめ: blocks エディタや preview toggle が見える画面だと強い。
-->

---

## 5. ディレクトリビューと permalink

直近で大きく進んでいるのが、ページ階層と permalink まわりです。

NeNe Records は WordPress の親子ページツリーをそのまま真似るのではなく、**任意 permalink と 301 リダイレクト**を中心に設計しています。

たとえば:

```text
/docs/getting-started
/docs/api/openapi
/company/about
```

のようなパスをレコード単位で持たせ、変更時には旧 URL から新 URL へ 301 を作れます。

管理画面では permalink 由来のディレクトリ表示、ツリー検索、DnD 親移動、手動並び順などが入り始めています。

WordPress から移行するときに、URL 構造や SEO 資産をなるべく壊さないための機能です。

<!--
スクリーンショット 5（推奨）:
掲載場所: 「WordPress から移行するときに...」の段落直後。
画面: ディレクトリビュー。
目的: 直近の強みである permalink ツリー、検索、DnD、並び替えを見せる。
写す要素: ディレクトリツリー、子件数、検索、並び替え操作。
おすすめ: 階層が2〜3段あるデータで、permalink パスが読める状態。
-->

---

## 6. 公開ページと SEO

公開ページは、SPA だけに寄せず、PHP 側でクローラブルな HTML を返す方針です。

NeNe Records では、公開レコードに対して:

- `<title>`
- canonical
- OGP
- Twitter card
- JSON-LD
- BreadcrumbList
- `sitemap.xml`
- `robots.txt`

などを扱います。

内部的には、PHP 前段で SSR 相当の head を作り、その上に SPA shell が乗る構成です。Node.js SSR を別に立てるのではなく、単一オリジンの PHP アプリとして扱うのが特徴です。

<!--
スクリーンショット 6（推奨）:
掲載場所: 「単一オリジンの PHP アプリとして扱うのが特徴です。」の直後。
画面: 公開ページをブラウザで表示している画面。
目的: 管理したレコードが実際に公開ページとして見えることを示す。
写す要素: 公開 permalink、記事タイトル、本文、テーマ反映。
余裕があれば DevTools で title / og:title / JSON-LD が入っていることも別カットで見せる。
-->

---

## 7. WordPress WXR インポート

NeNe Records は WordPress 互換ではありません。

ただし、WordPress から移行するための WXR インポート機能は実装されています。

扱える範囲:

- posts / pages / tags の取り込み
- メディア添付の取り込み
- 本文内画像 URL の差し替え
- 301 リダイレクトマップ
- Yoast / RankMath / AIOSEO の SEO メタ取り込み
- インポート計画のプレビュー

これは「WordPress のプラグインやテーマをそのまま動かす」ための機能ではなく、コンテンツと URL / SEO 資産を NeNe Records 側へ移すための機能です。

<!--
スクリーンショット 7（推奨）:
掲載場所: 「コンテンツと URL / SEO 資産を...移すための機能です。」の直後。
画面: `/admin/import` の WordPress WXR インポート画面。
目的: WordPress 移行に向けた機能があることを見せる。
写す要素: WXR import、preview、redirect / media / SEO 取り込みの項目。
注意: 実サイト由来の URL や非公開コンテンツが写る場合はダミー化する。
-->

---

## 8. 実データでの検証: 青空文庫インポート

最近は、実データを使った soak も進めています。

その一例として、青空文庫の公開テキストを使い、小説をレコードとして投入する importer を用意しています。

```text
tools/soak-import-aozora.php
```

検証環境では、夏目漱石の作品を章モードで投入し、1000件を超えるレコードを実際に扱っています。

実際に投入しているテナントはこちらです。

```text
https://aozora.nene-records.com/
```

ブラウザからアクセスすると、NeNe Records の公開シェル上で表示されます。`curl` などで `Accept: */*` のまま叩くと API 側の JSON が返ることがありますが、通常のブラウザアクセスでは HTML として表示されます。

この検証で見ているのは、単に「レコードを作れるか」だけではありません。

- 多数レコードで一覧が重くならないか
- permalink ツリーが破綻しないか
- 章ナビゲーションが読書 UI として成立するか
- sitemap / 公開ページ / 検索が実データで動くか
- 管理画面のディレクトリビューが大きめのデータ量に耐えるか

NeNe Records は「CMSっぽい管理画面を作った」だけでなく、実データを入れて使いながら、ディレクトリ表示、ページング、章ナビ、permalink、公開 SEO を磨いている段階です。

<!--
スクリーンショット 8（任意だが強い）:
掲載場所: Aozora セクション末尾、「公開 SEO を磨いている段階です。」の直後。
画面: 青空文庫データを投入した公開ページ、または章ナビ付きの小説ページ。
目的: 実データ・多数レコードで動かしていることを見せる。
写す要素: 章ナビ（前/目次/次）、公開 permalink、一覧またはディレクトリビュー。
おすすめ: `aozora.nene-records.com` をブラウザで表示した画面。root が期待と違う場合は、見えている章/一覧ページの実URLを使う。
-->

---

## 9. OpenAPI と MCP

NeNe Records は API-first です。

管理 UI も公開 UI も、基本的には API のクライアントです。

API 契約は OpenAPI 3.1 として管理され、CI で検証されます。

```text
http://localhost:18082/openapi.php
```

また、`docs/mcp/tools.json` に MCP tool catalog もあります。

重要なのは、MCP が DB に直接触るのではなく、OpenAPI で定義された HTTP/API 境界を通ることです。

これは NENE2 と NeNe シリーズ全体で共通している方針です。

<!--
スクリーンショット 9（任意）:
掲載場所: 「これは NENE2 と NeNe シリーズ全体で共通している方針です。」の直後。
画面: OpenAPI または MCP tools catalog。
目的: API-first / MCP-ready な構成を見せる。
写す要素: `/openapi.php`、または `docs/mcp/tools.json` の一部。
おすすめ: OpenAPI の方が読者に伝わりやすい。MCP catalog は補助カットで十分。
-->

---

## 10. 向いているケース、向いていないケース

向いているケース:

- WordPress より型のあるデータ管理をしたい
- JSON API を中心に CMS / 業務データ管理を作りたい
- 管理 UI、公開ページ、API、MCP の境界を分けたい
- WordPress からコンテンツと URL を移行したい
- self-host できる OSS を試したい

向いていないケース:

- WordPress プラグインをそのまま使いたい
- WordPress テーマ互換を期待している
- Elementor のように任意 JS まで自由に入れたい
- DB を直接触る MCP ツールを作りたい
- 共有ホスティングに ZIP を置くだけで本番運用したい

特に最後の点は注意です。Docker / VPS 向けの構成は整ってきていますが、共有ホスティング向けの ZIP インストーラーは今後の作業です。

---

## まとめ

NeNe Records は、NENE2 上に作っている API-first な型付き CMS / 業務データ管理 OSS です。

- Entity Type / Field Definition / Record で型付きデータを扱う
- React 管理 UI で schema と records を編集する
- 公開ページ、permalink、SEO / SSR も扱える
- WordPress WXR インポートで移行の入口を持つ
- 青空文庫のような実データ投入で、公開ページや一覧の挙動を検証している
- OpenAPI と MCP tool catalog を持つ
- マルチテナントや role / organization スコープを前提にしている

まだ開発中の部分はありますが、Docker で動かして、型付き CMS としての基本的な流れを試すには十分な状態になっています。

WordPress の完全互換を目指すのではなく、**型、API、AI ツール境界をはっきりさせた軽量 CMS** として育てています。

---

## リンク

| 種類 | URL |
| --- | --- |
| リポジトリ | https://github.com/hideyukiMORI/nene-records |
| NENE2 | https://github.com/hideyukiMORI/NENE2 |
| OpenAPI | https://github.com/hideyukiMORI/nene-records/blob/main/docs/openapi/openapi.yaml |
| Product Vision | https://github.com/hideyukiMORI/nene-records/blob/main/docs/explanation/product-vision.md |
| Docker guide | https://github.com/hideyukiMORI/nene-records/blob/main/docs/development/docker.md |
| Deployment guide | https://github.com/hideyukiMORI/nene-records/blob/main/docs/deployment.md |

フィードバックは [GitHub Issues](https://github.com/hideyukiMORI/nene-records/issues) へ歓迎します。
