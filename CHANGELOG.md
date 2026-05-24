# Changelog

All notable changes to NeNe Records are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
NeNe Records does not yet follow Semantic Versioning — entries are grouped by milestone.

---

## [Unreleased]

### Added
- `CHANGELOG.md` — this file (#94)
- `SECURITY.md` — vulnerability reporting policy (#97)
- `llms.txt` — LLM crawler summary (#96)
- README badges and OSS-ready copy (#98)

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
