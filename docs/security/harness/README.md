# Security assessment harness

Reproduces the live-attack environment used for the assessments in the parent directory. It boots an
**isolated, disposable** NeNe Records instance from the repo's own `compose.yaml` under a dedicated Compose
project (`nene-records-sectest`) on non-production ports, so it never collides with the dev stack and never
touches production.

> ⚠️ For your own, authorized, isolated instance only. Never point this at production
> (`*.nene-records.com`, `*.ayane.co.jp`) or any third-party system.

## Run

```bash
cd <repo root>
bash docs/security/harness/probe.sh
```

`probe.sh`:

1. Boots `app` + `mysql` under project `nene-records-sectest` (app on :18092, mysql on :13318) with a
   throwaway JWT secret and `APP_DEBUG=false` (production-like error handling).
2. Installs two orgs (`default`, `ayane`) each with an admin, logs in, and seeds a webhook (with a secret)
   and a draft entity.
3. Runs the attack battery and asserts the expected result for each case:
   - unauthenticated GET on admin routes → **401** (F-01)
   - cross-tenant cookie / JWT-bearer replay → **403** (F-02)
   - CSRF (cookie mutation without `X-Requested-With`) → **403**
   - superadmin route as non-superadmin → **403**, unauthenticated → **401** (#797)
   - JWT `alg:none` / malformed → **401**
   - version-disclosure headers absent (F-03)
4. Tears everything down with `docker compose -p nene-records-sectest down -v` (destroys the DB volume).

Captured cookies / tokens / output are git-ignored (see `.gitignore`). The throwaway JWT secret in the
script is a test value with no production meaning.
