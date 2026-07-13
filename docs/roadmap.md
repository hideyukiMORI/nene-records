# Roadmap

NeNe Records is an API-first flexible entity platform on NENE2 — lighter, typed, and MCP-ready compared to WordPress-style CMS monoliths.

## North Star

Developers and AI agents can:

- define entity types and typed fields through an admin frontend
- persist and query records through a documented JSON API
- operate the same data through MCP tools in natural language
- swap admin UI, consumer views, or API hosting independently
- isolate data per organization in a multi-tenant deployment

## Phase 0: Governance and Foundation ✅

Goal: make contribution, AI operation, and NENE2 inheritance unambiguous.

- README and contribution guide
- Issue-driven workflow and commit conventions
- inheritance map from NENE2
- ADR 0001 (governance inheritance)
- Cursor rules for agents
- self-review checklist policy

Tracked by `docs/milestones/2026-05-governance-and-foundation.md`.

## Phase 1: MVP Entity CRUD ✅

Goal: smallest vertical slice proving the entity model.

- `entity_types`, `entities`, `text_fields` tables and migrations
- CRUD API + OpenAPI
- PHPUnit + SQLite tests
- NENE2 consumer project scaffold

## Phase 2: Additional Field Types ✅

Goal: expand typed storage beyond text.

- int, enum, bool, datetime field tables
- schema registry API (`field_defs`)
- validation against registered schema on write

## Phase 3: Relations, Tags, and Query ✅

Goal: support real CMS/app patterns.

- tag and relation models
- filtered list/search API
- pagination helpers

## Phase 4: Admin Frontend ✅

Goal: schema and record management UI.

- React + TypeScript admin
- entity type editor
- record CRUD screens, Markdown editor, media upload

## Phase 5: MCP and Analytics ✅

Goal: AI-native operations and basic reporting.

- MCP tool catalog for entity CRUD and queries
- access log + date-based aggregation API
- admin dashboard (access stats, publish summary)

## Phase 6: Consumer Views ✅

Goal: replaceable public presentation patterns.

- `/view/{type}/{slug}` list/detail
- public bootstrap / site settings API
- navigation menu rendering

## Phase 7: CMS Hardening ✅

Goal: operate as a small headless CMS — auth, publish workflow, media, SEO, revisions, comments.

Tracked by `docs/milestones/2026-06-cms-mid-term.md`.

| Milestone | Status | Completed |
| --- | --- | --- |
| M1 — Usable Blog CMS | ✅ 完了 | 2026-05-24 |
| M2 — Team-Ready CMS | ✅ 完了 | 2026-05-25 |
| M3 — Headless CMS Platform | ✅ 完了 | 2026-05-26 |
| M4 — 機能拡充・使い勝手改善 | ✅ 完了 | 2026-05-26 |
| M5 — ユーザー管理・メディアライブラリ | ✅ 完了 | 2026-05-27 |
| M6 — コメント機能 | ✅ 完了 | 2026-05-27 |
| M7 — マルチテナント基盤・スーパー管理画面 | ✅ 完了 | 2026-05-27 |
| M8 — マルチテナント運用機能 | ✅ 完了 | 2026-05-27 |
| M9 — マルチテナント完成（1ユーザー=1組織） | ✅ 完了 | 2026-07-01 |
| M10 — マルチテナント包括テスト + E2E 拡充 | ✅ 完了 | 2026-05-27 |
| M11 — 通知・運用機能の拡充 | ✅ 完了 | 2026-05-28 |
| M12 — メディアライブラリ強化（ストレージ抽象化・派生画像） | ✅ 完了 | 2026-06-12 |
| M13 — 管理コンソール再設計 | ✅ 完了 | 2026-06-12 |
| M14 — 公開レイアウト & Custom Page | ✅ 完了 | 2026-06-12 |
| M15 — ウィジェット（Epic #324） | ✅ 完了 | 2026-06-13 |

詳細は `docs/todo/current.md` のマイルストーン別セクション、および `CHANGELOG.md` を参照。

## Phase 8: Public Product & Distribution ✅（コア出荷済み・継続中）

Goal: 「非技術者の入口」を塞ぎ、公開プロダクトとして配布・運用できる状態にする。

- **M16 — 公開サイトのテーマシステム**（Epic #367・ハイブリッドテーマ engine #390）✅
- **公開 SEO/SSR**（実 permalink でクローラブルな PHP 前段＋SPA シェル、OG 画像自動生成、sitemap/robots、Epic #537）✅
- **WordPress 移行**（WXR インポート＋メディア取込＋301 マップ、Epic #539）✅
- **公開サイト多言語化**（ロケール交渉＋hreflang、Epic #540）✅
- **本番配備 & subdomain マルチテナント SaaS**（https://nene-records.com・自動 TLS・メール認証・公開 signup）✅
- **Tier A zip インストーラ**（共有ホスティング向け自己完結 zip・`Nene2\Install` toolkit・v0.5.0〜**v0.5.2**）✅
- **SaaS org → Tier A 移送経路**（衝突マージ・id リマップ・トランザクション化・CLI import、#741）✅ 継続（カバレッジ拡充 #796/#798）
- **公開サイト堅牢化**（org 単位メンテナンスモード #813・per-org CSP trusted-embed #802・フォント self-host #818）継続中

継続フロンティア: 設定 UI 化 #541 / 権限細分化＋2FA #543（#536 P1）、リッチページ #491 / Custom Page #311、フロント全体監査 #507。
詳細は `docs/todo/current.md` を正本とする。

## Next Phase Candidates

Issue 化してから着手。詳細: `docs/todo/current.md` § 次フェーズ候補。

| Item | Overview |
| --- | --- |
| Popular-posts 精度向上 | per-view ロギングで管理 GET を除外し人気記事を正確化 |
| Widget ordering UI | region 内のウィジェット並び替え |
| BookViewer（画像連載リーダー） | 4コマ／画像連載向けページ送りリーダー＝章モードの拡張（コア無制限・#646 系） |
| AI Consumer Chat | NeNe Records を外部 API として使うチャットシステム（**別リポジトリ**） |

> 完了済み（旧候補）: コメント通知・スパム対策（M11 #263/#264）、公開検索（M15 #335）、メディア強化（M12 #299）、Consumer i18n（Epic #540）。

## Non-Goals

- Rebuilding Laravel/Symfony-style full-stack framework features in this repo
- WordPress plugin/theme compatibility
- Direct database access from MCP or admin frontend
