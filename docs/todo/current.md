# Current Work

Last updated: 2026-05-27

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

PHPUnit 545 tests / PHPStan level 8 / CS-Fixer / OpenAPI / MCP + Playwright 157 E2E tests — 全グリーン。

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

| Issue | 項目 | 概要 | 難易度 | 優先度 |
| --- | --- | --- | --- | --- |
| #263 | コメント通知 | 新規コメント時にメール通知（MailerInterface 活用） | 小 | P1 |
| #264 | コメントスパム対策 | honeypot / レート制限 | 小〜中 | P1 |
| #265 | コンテンツ検索 UI | Admin SPA に全文検索フォーム追加 | 小 | P2 |
| — | AI Consumer Chat | NeNe Records を外部 API として使うチャットシステム（**別リポジトリ**） | 大 | P2 |
| — | スケジュール公開改善 | `scheduled_at` ジョブ状態 UI・失敗ハンドリング | 小〜中 | P2 |

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
