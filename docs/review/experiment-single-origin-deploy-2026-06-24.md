# 実験レポート — 単一オリジン本番デプロイの実機検証

- 実施日: 2026-06-24
- 目的: #537 で実装した「単一オリジン（PHP前段＋SPAシェル）」配信が、**本番モード（`APP_DEBUG=false`・ビルド済み dist・Apache `/assets` alias）の実コンテナ**で end-to-end 動作するかを検証する。市場再討論 #551 が唯一の"今すぐ採用"阻害とした「公開ドメインでの実証」を、使い捨ての実験コンテナで先取り確認する。
- 結論（先に）: **構造的に完全成功**（SSR＋本番SPAマウント＋静的アセット配信＋SPAシェルフォールバック＋/view 301＋API/404 がすべて期待どおり動作）。**ただし本番限定の課題を1件発見**＝フレームワーク既定 CSP `default-src 'self'` が SPA の inline style（runtime テーマ上書き）と data: フォントをブロックする。見た目は外部CSSが担うため良好だが、要追従。

---

## 1. 実験のセットアップ（使い捨てコンテナ）

`compose.experiment.yaml`（**未コミット・実験後削除**）で、既存スタックと同じ MySQL に接続する本番モードのサービス `app-prod` を1つ追加:

| 項目 | 値 |
| --- | --- |
| イメージ | `docker/php/Dockerfile` からビルド（**`Alias /assets → frontend/dist/assets` を含む**） |
| `APP_DEBUG` | **`false`**（＝prod モード: SSR は manifest 由来の本番SPAをマウント） |
| ポート | `18092:80`（既存 :18082 dev とは別） |
| DB | 既存 `mysql` サービスを共有（`NENE_RECORDS_SKIP_MIGRATE=1` で migration 副作用を回避） |
| フロント | 事前に `npm run build`（dist + `.vite/manifest.json`、entry=`/assets/index-CjQDD3Gf.js`） |

起動: `docker compose -f compose.yaml -f compose.experiment.yaml up -d --build app-prod` → 1リトライで health=200。

---

## 2. 結果 — HTTP スモーク（:18092・本番モード）

| # | 検証 | リクエスト | 結果 | 判定 |
| --- | --- | --- | --- | --- |
| 1 | 実URLクローラブルSSR | `GET /posts/246` | 200 `text/html`。head に canonical/OGP/JSON-LD ＋ **manifest由来の本番アセット**（`/assets/index-CjQDD3Gf.js` ＋ css×2 ＋ modulepreload×6）。`#root` で本文を内包。**`@vite/client` なし** | ✅ |
| 2 | 静的アセット配信（Apache alias） | `GET /assets/index-CjQDD3Gf.js` | 200 `text/javascript` 448,666 B | ✅ |
| 2 | 同上（CSS） | `GET /assets/index-CK1XO_dP.css` | 200 `text/css` 312,542 B | ✅ |
| 3 | SPAシェルフォールバック | `GET /admin`・`/admin/users`・`/login`・`/posts`・`/search`・`/tag/php` | **すべて 200** `text/html`（dist/index.html＝`<title>NeNe Records Admin</title>`＋`#root`） | ✅ |
| 4 | /view ツインの正規化 | `GET /view/posts/blocks-showcase` | **301 → `/posts/246`** | ✅ |
| 5 | API は機能維持 | `GET /api/v1/entities?per_page=1` | 200 `application/json` | ✅ |
| 5 | API/不在は genuine 404（passthrough） | `GET /api/v1/nonexistent`（html）・`GET /posts/99999` | 404（SPAシェルにフォールバックしない） | ✅ |

→ 単一オリジンの全経路（API / 静的 / SSR / SPAシェル / リダイレクト / 404）が設計どおり。**`/assets` alias は dist を正しく配信**し、prod の SSR ページは **Vite ではなく本番ビルドのSPA** をマウントする。

## 3. 結果 — ブラウザ実機（Playwright・headless Chromium）

| ページ | `#root` 子要素 | h1 | SPAマウント | console error |
| --- | --- | --- | --- | --- |
| `/posts/246`（公開詳細） | 1（SSR本文を置換） | ブロックシステム ショーケース | ✅ | **56（全てCSP違反）** |
| `/posts`（ブラウズ） | 1 | Post | ✅ | 0 |
| `/admin`（管理ログイン） | 1 | NeNe Records Admin | ✅ | 0 |

- **SPA は本番単一オリジンで実際にマウント・描画**した（`#root` の SSR フォールバックを React `createRoot().render` が置換）。スクリーンショット（`scratchpad/shots/exp-public-detail.png`）では、テラコッタ系アクセント・hero・ナビ・ダーク/ライト切替まで**本番品質でテーマ適用済み**に見える。
- 公開詳細ページのみ **56件の CSP 違反**を検出（後述）。ブラウズ/管理は 0 件。

---

## 4. 発見した課題（本番限定）— 既定 CSP が SPA の inline style / data: フォントをブロック

### 事象
`/posts/246` で 56 件の Content-Security-Policy 違反:
- `Applying inline style violates ... 'default-src 'self''`（inline `<style>` がブロック）
- `Loading the font 'data:font/woff2;base64,...' violates ... 'default-src 'self''`（data: フォントがブロック）

### 原因
NENE2 の `SecurityHeadersMiddleware` が **dev/prod 共通で `Content-Security-Policy: default-src 'self'` を付与**（アプリ側はカスタム CSP 未設定＝既定のまま）。`default-src 'self'` には `style-src`/`font-src` が無いため、以下が落ちる:
- SPA の **runtime テーマ上書き**（管理者カスタマイズのトークンを JS が inline `<style>` で注入）
- **data: URI 埋め込みフォント**（`@/fonts` の FOUC 回避用 base64 woff2）

> dev では公開サイトは通常 :18084（Vite・CSPなし）経由のため**この問題は顕在化しない**。PHP が SPA を配信する**単一オリジンで初めて露見**＝まさに本実験が炙り出した本番限定の挙動。

### 影響度（実測）
- **見た目は概ね良好**: 本体スタイルは外部CSS（`/assets/*.css`＝`'self'`で許可）が担うため、デフォルトテーマでは正しく描画される（スクショ参照）。
- ただし **admin の runtime テーマ上書きが本番で効かない**（inline `<style>` がブロックされ既定トークンにフォールバック）、data: フォントは system フォールバック。コンソールは違反で汚れる。

### 推奨対応（follow-up・小）
`SecurityHeadersMiddleware` は `hasHeader` チェック付き（**ハンドラが先に CSP を出せば上書きしない**）。よって**公開HTMLレスポンス（SSRレコード＋SPAシェル）にだけ緩和CSPを付与**するのが筋:
- 例: `default-src 'self'; style-src 'self' 'unsafe-inline'; font-src 'self' data:; img-src 'self' data:; connect-src 'self'`
- API レスポンスは `default-src 'self'` のまま厳格維持。
- 理想は inline style に nonce/hash を付ける案だが、runtime テーマ注入の都合上まずは `'unsafe-inline'`（style 限定）が現実的。

→ エピック #536 の P2「公開ページのCSP backstop」と統合して別 Issue 化推奨。

---

## 5. 結論

- **#537 の単一オリジン配信は本番モードの実コンテナで end-to-end 動作する**ことを実証した（市場再討論 #551 が求めた「公開ドメインでの実証」をコンテナで先取り確認）。残っていたのは純粋に「実際にデプロイする」ことだけで、コードは本番で機能する。
- 実デプロイ手順（確認済み）: ① `npm run build --prefix frontend` ② `docker/php/Dockerfile`（`/assets` alias 込み）でイメージビルド ③ `APP_DEBUG=false` で起動 ④ 公開ドメインをこのコンテナに向ける。
- **唯一の追従**: 公開HTMLレスポンスの CSP を SPA 対応に緩和（上記4）。これが無くてもページは描画されるが、runtime テーマ上書きとフォントが本番で degrade する。

## 6. 後片付け
実験コンテナ `app-prod` は停止・削除、`compose.experiment.yaml` も削除（リポジトリに残さない）。既存 dev スタック（:18082 等）は不変。スクリーンショットは `scratchpad/shots/exp-*.png`（セッション一時領域）。
