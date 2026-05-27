# Changelog

All notable changes to NeNe Records are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
NeNe Records does not yet follow Semantic Versioning — entries are grouped by milestone.

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
