# 引き継ぎ: front_page 機能 — PR #702 レビュー結果と残作業

- 作成: 2026-07-03（Fable。PR #702 の multi-agent レビュー完了時点のスナップショット）
- 機能設計の正本: `docs/handoff-2026-07-03-front-page.md`（指示書。§4-4 のみ実装で設計変更あり、下記参照）
- Issue: #701 / PR-1: #702（feat/701-front-page-setting, OPEN）
- レビューコメント（指摘全文）: https://github.com/hideyukiMORI/nene-records/pull/702#issuecomment-4869767377

---

## 1. 現状詳細

### 完了していること（PR #702 = PR-1: バックエンド＋公開挙動）

- **設定 seed**: `database/migrations/20260714000000_add_front_page_setting.php` —
  `front_page`（text・レコードID・is_public=1・default ''）を per-org 冪等 insert。
  版数は既存の未来日付 migration 群（〜20260713）の後ろで silent-skip 回避済み。
- **保存検証**: `src/Setting/UpdateSettingUseCase.php::validateFrontPage` —
  `''` 許可 / 非空は ctype_digit＋org内存在＋未削除＋published。違反は
  `SettingValueInvalidException`（HTTP 422 マッピング済み）。
- **公開解決**: `src/Setting/ListPublicSettingsUseCase.php::resolveFrontPagePath` —
  ID → canonical permalink パス（無効は `''`）。media 型の id→URL と同じ流儀。
- **`/` の SSR**: `src/PublicRecord/RenderPublicHomeHandler.php` — **エッジ層**
  （`src/Http/SingleOriginKernel.php` の app 後段）。front_page が有効な公開レコードなら
  `renderEntity(asFrontPage: true)` で SSR（canonical=ルート・og:type=website・パンくず抑制）。
  無効/未設定/apex は素通し → 従来トップ。`SpaShellFallback` は text/html を素通しに修正済み。
- **301**: `src/PublicRecord/RenderPublicRecordViewHandler.php:94-99` —
  ピン留めレコードの元 permalink → `/`（type/custom 両経路）。
- **sitemap**: `GenerateSitemapUseCase` — ピン留めレコードの個別URLを `/` に置換。
- **共通 reader**: `src/PublicRecord/FrontPageSetting.php`（ただし数字チェックのみの弱い版。§3-6）。
- **frontend の一部**: `frontend/src/features/manage-front-page/hooks/useFrontPage.ts` 等の
  hook 層と、`router.tsx` の `PublicHome` → `PublicRecordByPermalink path={site.frontPagePath}`
  分岐は入っている（View/UI の完成は PR-2）。
- 検証: composer check 全緑（PHPUnit 1136）・実機 curl（SSR/301/sitemap/フォールバック）済み。

### 指示書からの設計変更（重要・正当と判断）

指示書 §4-4 の「`GET /` をアプリルートに登録」は **NENE2 既存 `/` ルートに登録順で負ける**
ことが実機で判明 → **エッジ層方式**（SingleOriginKernel の app 後段で `/` を横取り）に変更。
framework `/`（API クライアント向け JSON）は保持しつつ HTML の home だけ上書きする。
レビューでもこの選択自体は妥当と判断（ただし §3-1 の上流ガード欠落が代償として発生）。

### レビューで「問題なし」と確認済みの領域（再調査不要）

DI配線 / preview 経路（`GetPublicRecordViewOutput` 共有）/ エッジ層の org スコープ
（`RequestScopedHolder` は app 処理後も有効・apex は org 0 で front_page seed なし）/
CSP・GA・base-href の SSR パリティ / sitemap N+1 なし / ETag は resolved 値ベースで正しく無効化 /
`'0'`・巨大数・空白などの境界値 / `SettingValueInvalidException`→422。

---

## 2. 残作業の全体像

1. **PR #702 マージ前の修正 3 件**（§3-1〜3-3。小粒・必須）＋任意で §3-4
2. **PR #702 マージ**
3. **PR-2（管理UI）**: 指示書 §5 の「ホームページの表示」セクション＋i18n 6ロケール。
   その際にレビュー指摘 §3-5〜3-7 を同乗させる。PR-2 が `Closes #701` を持つ。

---

## 3. 修正指示書（レビュー指摘の潰し方）

### 3-1.【マージ前・必須】エッジ層の上流ガード欠落 — CONFIRMED・中

`src/PublicRecord/RenderPublicHomeHandler.php:39` の `apply()` が `$response` を一切見ずに
`/` を上書きするため、ThrottleMiddleware の 429 や一時的 5xx をフレッシュな 200 SSR で
マスクし、`/` がレート制限の効かない multi-query エンドポイントになる。

**修正**: `resolveFrontPage()` を呼ぶ前に上流ガードを追加:

```php
// 横取りするのは「200 の framework-info（非HTML）」のときだけ。
// 429/5xx/既に text/html の応答は素通しして throttle・エラーを保つ。
if ($response->getStatusCode() !== 200
    || str_contains($response->getHeaderLine('Content-Type'), 'text/html')) {
    return $response;
}
```

テスト: SingleOriginKernel テストに「上流 429 → front_page 設定済みでも 429 のまま
（Retry-After 保持）」「上流 500 → 500 のまま」を追加。
（`SpaShellFallback` が 429 を shell で覆う挙動は pre-PR からの既存で、本修正の対象外。
直したければ別 Issue。）

### 3-2.【マージ前・必須】301 Permanent＋クエリ捨て — CONFIRMED・中

`src/PublicRecord/RenderPublicRecordViewHandler.php:94-99`。`front_page` は可変設定なのに
301（Cache-Control 無し）なので、ピン解除後もブラウザ/CDN のキャッシュが
`元permalink → /` を恒久転送し続け、元URLに到達不能になる。Location はクエリを落とすため
`?lang=fr` などのロケール意図も消える（`/` の SSR は `?lang` を読むので維持すれば動く）。

**修正**: この分岐だけ **302** に変更し、クエリを維持:

```php
$home = $uri->getScheme() . '://' . $uri->getAuthority()
    . BasePath::prefix($this->effectiveBase($request), '/');
$query = $uri->getQuery();
if ($query !== '') {
    $home .= '?' . $query;
}
return $this->responseFactory->createResponse(302)->withHeader('Location', $home);
```

`/view/...` 系の既存 canonical 301 は対象が安定しているので**変更しない**。
テスト: 301→302 の期待値更新＋「?lang=fr が Location に残る」ケース追加。

### 3-3.【マージ前・必須】PR 本文の `Closes #701` — CONFIRMED

本文は「PR-2 で close」と書きつつ末尾に `Closes #701`。マージ即 #701 自動クローズになる。

**修正**: `gh pr edit 702 --body-file <修正済み本文>` で末尾を `Refs #701` に変更
（本文の他の部分は変えない）。PR-2 側が `Closes #701` を持つ。

### 3-4.【任意・PR-1 同乗可】トップページの JSON-LD が BlogPosting — CONFIRMED・低

`templates/public/record-detail.php:41-48` の `@type` が無条件 `BlogPosting`。
front page では `og:type=website` と矛盾。

**修正**: `$asFrontPage`（テンプレ変数として渡すか `$ogType === 'website'` で判定）のとき
`@type` を `WebPage` にし、`datePublished`/`dateModified` を省略。1行〜数行。

### 3-5.【PR-2】SPA 内遷移で permalink→`/` が再現されない — CONFIRMED・低

`frontend/src/pages/consumer/PublicRecordDetailPage.tsx:295` 周辺。`useCanonicalRedirect` は
自URL=canonical のピン留めレコードでは発火しない。タイプ一覧 `/pages`・RelatedRecords・
RecentPostsWidget からの SPA 遷移で同一コンテンツが 2 URL でその場描画される
（ハードリロード時だけ 301/302）。

**修正**: `PublicRecordDetailContent`（または `useCanonicalRedirect`）で
`site.frontPagePath` と現在パスを比較し、一致したら `/` へ `Navigate replace`。
`isFrontPage`（`/` ルートでの描画）時は当然スキップ。
※ その際、現在 4 段プロップドリリングになっている `isFrontPage` を
`PublicSite` context 参照に置き換えると §3-7 の余談も一緒に片付く。

### 3-6.【PR-2】front_page ルールの4箇所重複・弱い共有 reader — PLAUSIBLE・保守性

「front page とは何か」が 4 実装に分散:
`FrontPageSetting::pinnedRecordId`（数字チェックのみ・**published 検証なし**）/
`RenderPublicHomeHandler::resolveFrontPage`（フル）/
`ListPublicSettingsUseCase::resolveFrontPagePath`（フル）/
`UpdateSettingUseCase::validateFrontPage`（write側フル）。
今日の実バグは無いことを検証済み（301 は UseCase の 404 ガードが先行、sitemap は
published のみ走査）だが、新しい呼び出し元が素の `pinnedRecordId()` を使うと即バグ化する。

**修正**: `FrontPageSetting` に published 解決を一本化
（例: `resolvePublished(): ?array{Entity, EntityType}`）し、4 箇所すべてそれを呼ぶ。
`RenderPublicHomeHandler` からは `SettingRepositoryInterface` 直依存を外せる。

### 3-7.【PR-2・任意】不要な先行解決の効率 — CONFIRMED・実害小

- `ListPublicSettingsUseCase::execute()` が全レコードSSRの `publicSettingsMap()` 経由で
  使われない front_page 解決（+2 PKクエリ）を実行
- `pinnedRecordId()` の単発 SELECT ＋直後のバッチ読みで `setting_values` 二重読み
- public settings エンドポイントは 304 でも解決コストを払う

**修正**: §3-6 の集約とセットで、リクエスト内メモ化 or 「解決は public settings API の
レスポンス生成時のみ」に遅延。ブロッカーではない。

### 3-8.【対応不要・記録のみ】path（ディレクトリ）型テナントで不発 — PLAUSIBLE・低

エッジ層は外側リクエストの `/org-slug/` を見るため front page SSR がテナントルートで
発火せず、301/302 の飛び先も JSON が返る `/org-slug/` になる。本番は subdomain モードで、
path モードのエッジ層 base_prefix 問題は **既知の穴**（`docs/handoff-2026-06-25.md:32`）。
既存課題を解消するときに front page も一緒に直ること、を当該メモに追記すれば十分。

### 採録上限で落とした軽微な観察（義務なし）

- バリデーション/公開解決テストの dataProvider 化（`UpdateSettingFrontPageValidationTest` 等）
- `RenderPublicHomeHandler.php` の `$entity->id === null` デッドガード（§3-6 で消える）
- `record-detail.php` の再インデント churn（blame 汚れ。次回から挙動変更行だけに）

---

## 4. 検証（修正後に回すもの）

```bash
composer check                    # PHPUnit + PHPStan L8 + CS-Fixer + OpenAPI + MCP
npm run check --prefix frontend   # PR-2 のとき

# 実機（API=18082）:
curl -s -o /dev/null -w '%{http_code}\n' -H 'Accept: text/html' http://localhost:18082/   # 200 SSR
# 429 パス: 短時間に121回叩いて 429 が保たれること（§3-1）
curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' 'http://localhost:18082/<permalink>?lang=fr'
#   → 302 で Location に ?lang=fr が残ること（§3-2）
```

## 5. 進め方メモ

- §3-1/3-2（＋任意 3-4）は PR #702 のブランチ `feat/701-front-page-setting` に追いコミット
  （Conventional Commits・日本語 description・`(#701)`）。§3-3 は `gh pr edit` のみ。
- PR-2 は指示書 `docs/handoff-2026-07-03-front-page.md` §5（管理UI・i18n）＋本書 §3-5〜3-7。
  PR-2 の本文に `Closes #701`。
