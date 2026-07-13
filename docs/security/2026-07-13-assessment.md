# Security Assessment — NeNe Records (2026-07-13)

**Type:** Authorized self / maintainer-run assessment — black-box live attack (ATK) + white-box code review.
**This is not a third-party penetration test.**
**App version at test:** `VERSION` 0.5.2, branch `sec/824-assessment-2026-07` (off `main` `1562814`).
**Scope:** NeNe Records application (`src/`, HTTP API + public SSR), exercised as a running service.
**Target:** disposable Docker stack (`php:8.4.23-apache` + mysql:8.4), isolated project `nene-records-sectest`
on non-production ports — never run against production data or `*.nene-records.com` / `*.ayane.co.jp`.
Reproduction: [`harness/`](harness/). Issue: #824.

---

## Result

Pre-remediation: **1 Critical, 1 Medium, 1 Low `EXPOSED`.** All three were remediated in this branch and
re-verified live (**post-fix: 0 `EXPOSED`**). Every other attack category was already blocked.

| # | Severity | Finding | Status |
|---|----------|---------|--------|
| F-01 | **Critical** | Unauthenticated read of the entire admin API (incl. webhook signing secrets, draft/unpublished records, full export, field values) | Fixed + regression-verified |
| F-02 | **Medium** | Cross-tenant read via JWT replay — org binding enforced only on capability-mapped routes | Fixed + regression-verified |
| F-03 | **Low** | Version disclosure via `Server` / `X-Powered-By` response headers | Fixed + regression-verified |

---

## Methodology

- **Black-box live attack (ATK):** the app was booted in a throwaway Docker stack, migrated, seeded with
  two organizations (`default` #1, `ayane` #2) and an admin each, then attacked over HTTP with a cracker
  mindset — malicious inputs aimed at breaking authentication, tenant isolation, injecting SQL, and leaking
  data. Source was not modified during attack; the containers and volumes were destroyed afterwards.
- **White-box review:** `AdminApiAuthMiddleware`, `CapabilityMiddleware` / `CapabilityResolver`,
  `OrgResolverMiddleware`, `SessionCookie`, and the OpenAPI route inventory were read to find the root cause
  of each observed behaviour.
- **Regression verification:** each `EXPOSED` finding was re-exercised after its fix with the exact input
  that previously triggered it, and the full `composer check` suite (1271 tests / 3609 assertions, PHPStan
  level 8, CS-Fixer, OpenAPI, conformance, MCP) was run green.

---

## Findings

### F-01 — Unauthenticated read of the admin API (Critical) — FIXED

**Observed.** With no credentials whatsoever:

```
GET /api/v1/webhooks              → 200, body includes "secret":"whsec_…"   (webhook signing secret)
GET /api/v1/entities?status=draft → 200, returns draft / unpublished records
GET /api/v1/entities/export       → 200, full export
GET /api/v1/text-fields           → 200, field content (incl. draft bodies)
GET /api/v1/notification-channels → 200
GET /api/v1/field-defs, /entity-types, /tags, /dashboard, /analytics/*  → 200
```

31 GET routes under `/api/v1/` were reachable unauthenticated. On the multi-tenant production
(`<tenant>.nene-records.com`), this exposed **each tenant's webhook signing secret** (enabling forged,
correctly-signed webhook payloads), all **draft/unpublished content**, and a **full data export** — to any
anonymous visitor.

**Root cause.** `AdminApiAuthMiddleware` protected only *non-GET* methods for `/api/v1/*` routes not listed
in `ADMIN_ONLY_PREFIXES` (rule 3). Any admin resource whose prefix was never added to that list leaked its
GET response. The intended design is that public reads go through the dedicated `/api/v1/public/*` surface
(and server-side SSR); admin APIs require auth.

**Remediation.** `AdminApiAuthMiddleware` now requires authentication for **every** method on `/api/v1/*`
except the CORS-preflight `OPTIONS` — GET/HEAD included (fail-closed). `ALWAYS_OPEN_PREFIXES`
(`/health`, `/api/v1/auth/*`, `/api/v1/public/*`, cron batch endpoints) are unchanged, so the public site and
login are unaffected. A newly added admin route is now protected by default instead of leaking its GET.

**Regression (live).** All the routes above now return **401**; the webhook secret is no longer reachable
unauthenticated; authenticated admin reads still return 200; `/health` and `/api/v1/public/*` stay open;
`OPTIONS` passes through. Unit regression in `tests/Auth/AdminApiAuthMiddlewareTest.php`.

**Defense-in-depth (recommendation, not blocking).** `WebhookHttpMapper` returns the `secret` field in
read responses. Consider a write-only secret (return a masked/last-4 value on read) so the signing secret is
never echoed back even to authenticated admins. Tracked as a follow-up.

### F-02 — Cross-tenant read via JWT replay (Medium) — FIXED

**Observed.** An authenticated user of org A, replaying their session JWT as an `Authorization: Bearer`
token against org B's host (or, before F-01's fix, any cross-org cookie), could read org B's data on routes
that `CapabilityResolver` does not map:

```
admin(org=1) cookie/bearer → GET /api/v1/webhooks on ayane(org=2) host → 200, returned org 2's webhook + secret
```

**Root cause.** `CapabilityMiddleware` enforced "JWT `org_id` must equal the resolved org id" **only after**
`CapabilityResolver::resolve()` returned a required capability. For routes with no capability mapping
(e.g. `GET /api/v1/webhooks`), the middleware returned early and skipped the org-scope check entirely.
Session cookies are host-only (a browser will not send them cross-subdomain), but a user can copy their JWT
and replay it manually as a bearer token, bypassing that mitigation.

**Remediation.** The organization-scope check now runs **unconditionally** for every authenticated,
org-scoped request (non-superadmin, where `OrgResolverMiddleware` resolved `nene2.org.id`), independent of
whether a capability is mapped. Superadmin (cross-org by design) and org-agnostic bypass routes
(`/superadmin/*`, `/organizations`, `/auth/*`) are unaffected because they never set `nene2.org.id`.

**Regression (live).** Cross-org cookie **and** bearer-replay now return **403** `org-access-denied`;
same-org admin access still returns 200; superadmin console gating (#797) still holds
(non-superadmin → 403, unauthenticated → 401). Unit regression in `tests/Auth/CapabilityMiddlewareTest.php`.

### F-03 — Version disclosure headers (Low) — FIXED

**Observed.** `Server: Apache/2.4.67 (Debian)` and `X-Powered-By: PHP/8.4.23` were returned on every
response, disclosing exact component versions.

**Remediation.** `ServerTokens Prod` + `ServerSignature Off` (in a `zz-hardening.conf` that loads after
Debian's `security.conf`) and `expose_php = Off` added to both `docker/php/Dockerfile` and
`docker/php/Dockerfile.prod`. **Live re-test:** `Server: Apache`, `X-Powered-By` absent. Takes effect in
production on the next image rebuild/deploy.

---

## Verified robust (no finding)

| Category | Test | Result |
|----------|------|--------|
| CSRF | Cookie-auth `POST` without `X-Requested-With` | **403**; with header → passes |
| Superadmin authz (#797) | Non-superadmin → `/api/v1/superadmin/*`; unauth → same | **403** / **401** |
| JWT algorithm confusion | `alg:none` forged token; malformed bearer | **401** |
| SQL injection | `' OR '1'='1`, time-based `SLEEP(3)`, path-param SQLi (authenticated) | **200**, ≤35 ms, no SQL error — parameterized |
| Error info leak | Force 404/500 with `APP_DEBUG=false` | RFC 9457 Problem Details; no stack trace / SQL / path |
| Response headers | Baseline | `CSP: default-src 'self'`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin` present |
| Session cookie | `nene_session` flags | `HttpOnly`, `SameSite=Lax`, `Path=/`, host-only (no `Domain`), `Secure` over HTTPS |
| HTML/SVG sanitization | Unit coverage | `PublicHtmlSanitizer` (6 tests), `SvgSanitizer` (17 tests) |

---

## Coverage & known limits (honest)

- Tested against the **dev container** image; production runs `Dockerfile.prod` (multi-stage). F-03's
  hardening was applied to both, but a prod-image rebuild should re-confirm the headers on deploy.
- **Not exhaustively fuzzed:** stored-XSS through `PublicHtmlSanitizer` on live SSR (relied on unit
  coverage + one spot check), media on-demand-derivative SSRF / path traversal, and webhook delivery SSRF.
  Recommended as the next round's focus.
- **Out of scope this round:** rate-limiting / DoS (destructive), Playwright E2E, dependency-CVE scanning,
  and the private-vulnerability-reporting flow.
- All numbers/behaviour are point-in-time for the version above and will drift as the code changes.
