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

See `CHANGELOG.md` for per-milestone details.

## Next Phase Candidates

Issue 化してから着手。詳細: `docs/todo/current.md` § 次フェーズ候補。

| Item | Overview |
| --- | --- |
| Org member management UI | org ごとのメンバー招待・ロール変更・org 切替 UI |
| Comment notifications | 新規コメント時にメール通知（MailerInterface 活用） |
| Comment spam protection | honeypot / CAPTCHA / レート制限 |
| Consumer i18n | 公開 detail の多言語化 |
| AI Consumer Chat | NeNe Records を外部 API として使うチャットシステム（**別リポジトリ**） |

## Non-Goals

- Rebuilding Laravel/Symfony-style full-stack framework features in this repo
- WordPress plugin/theme compatibility
- Direct database access from MCP or admin frontend
