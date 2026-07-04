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
   # → dist/nene-records-<version>.zip（Packagist の NENE2 を解決した自己完結 zip）
   ```

2. **アップロード**: ZIP を展開した中身一式をサーバーへアップロードする
   （例: `~/web/nene-records/` 配下に `src/ vendor/ public_html/ …` が並ぶ形）。

3. **公開フォルダを `public_html/` に向ける**: ホスティングの管理画面で、サイトの
   ドキュメントルート（公開フォルダ）をアップロード先の `public_html` に設定する。
   `.env` や `src/` は公開フォルダの外に置かれるのがこの構成の狙い。

4. **インストーラを開く**: ブラウザで `https://あなたのドメイン/install/` を開き、
   ウィザードに従う:
   - **ステップ1 — サーバー要件**: 全項目 OK になるまで解消して再チェック。
   - **ステップ2 — データベースと利用形態**: DB 接続情報を入力。`.env` の書き込みと
     migration（スキーマ初期化）まで自動で行う。利用形態は通常「単一サイト（標準）」の
     まま（マルチテナントは詳細設定から）。
   - **ステップ3 — 管理者アカウント**: サイト名・メール・パスワード（8文字以上）。
   - **完了**: 表示されたリンクから管理画面にログイン。

5. **後片付け（推奨）**: サーバー上の `public_html/install/` ディレクトリを削除する。
   削除しなくても `var/.installed` マーカーと DB 検査により再実行は 403 で遮断される。

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
