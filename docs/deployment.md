# Deployment & onboarding

NeNe Records ships **single-origin**: one PHP app (Apache, front controller
`public_html/index.php`) serves the API, the crawlable SSR for public records,
and the built SPA shell. Point one public domain at it — no separate Node
runtime.

## First-run onboarding (one command)

After the database is reachable, turn a fresh instance into a usable one with a
single command. It runs migrations, creates the initial organization (which
auto-seeds its default content types) and the admin user. **Idempotent** — safe
to re-run on every deploy.

```bash
docker compose exec -T \
  -e NENE_INSTALL_ADMIN_EMAIL=admin@example.com \
  -e NENE_INSTALL_ADMIN_PASSWORD='change-me' \
  app composer app:install
```

> `docker compose exec` does **not** forward host shell variables into the
> container — pass the install vars with `-e`, otherwise `install.php` reports
> `NENE_INSTALL_ADMIN_EMAIL and NENE_INSTALL_ADMIN_PASSWORD are required`.

| Env var | Required | Default |
| --- | --- | --- |
| `NENE_INSTALL_ADMIN_EMAIL` | yes | — |
| `NENE_INSTALL_ADMIN_PASSWORD` | yes | — |
| `NENE_INSTALL_ORG_NAME` | no | `NeNe Records` |
| `NENE_INSTALL_ORG_SLUG` | no | `default` |

The admin is created with the org-level `admin` role. Platform-level
`superadmin` accounts are provisioned separately (not via this command).

## Frontend assets

The SPA + crawlable SSR mount the built assets via the Vite manifest. Build once
per release:

```bash
npm ci --prefix frontend
npm run build --prefix frontend   # → frontend/dist (+ .vite/manifest.json)
```

Apache serves `frontend/dist/assets` (see `docker/php/Dockerfile`), and
`SingleOriginKernel` serves `frontend/dist/index.html` as the SPA shell for
client-routed paths. Without a build, the app still runs (SSR + API); only the
SPA shell fallback is a no-op.

## Going live

1. Provision the database and set the app `.env` (DB, JWT secret, ports, public
   URL). Committed reference values live in `.env.example`.
2. `composer app:install` (above).
3. Build the frontend (above).
4. Point the public domain at the PHP app and verify:
   - `curl -fsS https://<domain>/health` → `ok`
   - a public record permalink returns crawlable SSR HTML (`<title>`, OGP,
     JSON-LD) with the SPA mounting over it.

WordPress migration (WXR import → posts/pages/tags, 301 redirect map for old
URLs, media, SEO meta) is available in the admin UI under **データ移行**
(`/admin/import`).

## Production stack (`compose.prod.yaml`)

A standalone production stack — the single-origin PHP app + MySQL + cron, with
`APP_ENV=production`, `APP_DEBUG=false` (both fixed, immune to a stray dev `.env`)
and OPcache tuned for immutable code. No dev tooling (phpMyAdmin / Mailpit / Vite).

The app is a **self-contained immutable image** (`docker/php/Dockerfile.prod`,
multi-stage): the SPA/SSR assets are built in-image, PHP deps are installed
`--no-dev`, and code + vendor + dist are baked (no runtime bind-mounts, no manual
frontend build). nene2 (a sibling path repo) is injected via the `nene2` named
build context, so the build never needs the parent directory.

```bash
# 1. set required secrets in .env: NENE2_LOCAL_JWT_SECRET, DB_PASSWORD, MYSQL_ROOT_PASSWORD
# 2. build + start (frontend is built inside the image)
docker compose -f compose.prod.yaml up -d --build
# 3. first-run onboarding (idempotent)
docker compose -f compose.prod.yaml exec -T \
  -e NENE_INSTALL_ADMIN_EMAIL=admin@example.com \
  -e NENE_INSTALL_ADMIN_PASSWORD='change-me' \
  app composer app:install
```

The image entrypoint (`docker/app/entrypoint.prod.sh`) waits for the DB and runs
`migrations:migrate` on start; the migration set applies cleanly on a fresh MySQL
database (verified end-to-end). The build requires the nene2 checkout adjacent to
this repo (`../NENE2`). Set the public host port with `NENE_RECORDS_PROD_PORT`
(default 8080) and front it with TLS/reverse proxy.

**Single-org resolution.** A single-tenant deploy resolves its organization from
`ORG_SLUG`, which must match the install org slug (`app:install` default `default`).
The stack defaults `ORG_SLUG=default`; if you install with a custom slug, set
`ORG_SLUG` to match, otherwise every org-scoped request returns `org-not-resolved`
404. (Alternatively set `tenant_org_slug` via the system-config API.)

## Operations

**Health.** The `app` service has a Docker `healthcheck` that polls its own
`/health` route, so `restart: unless-stopped` can recover a hung process and
dependents wait for readiness. Check it with `docker compose -f compose.prod.yaml ps`
(the app shows `(healthy)`).

**Logs.** Apache access + error logs stream to the container's stdout/stderr (the
`php:apache` base image wires them there), so `docker compose -f compose.prod.yaml
logs -f app` follows live traffic. The app additionally records structured access
entries to the database (`access_log` table).

**Backups.** Dump the database to a timestamped, gzipped file (keeps the newest 14):

```bash
bash scripts/backup-db.sh                 # → ./backups/nene_records-YYYYmmdd-HHMMSS.sql.gz
# schedule it from the host crontab, e.g. nightly at 03:15:
#   15 3 * * * cd /path/to/deploy && BACKUP_DIR=/var/backups/records bash scripts/backup-db.sh >> /var/log/records-backup.log 2>&1
```

Restore a dump:

```bash
gunzip < backups/nene_records-XXXXXXXX-XXXXXX.sql.gz \
  | docker compose -f compose.prod.yaml exec -T mysql \
      sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
```

The script reads credentials from the running db container's own environment, so no
secrets are passed on the command line. Override `COMPOSE_FILE` / `DB_SERVICE` when
the stack uses different names (e.g. a Caddy-fronted deploy with `DB_SERVICE=db`).

## Not yet productized (tracked follow-ups)

- Pushing the built image to a registry + an orchestrated (multi-host / rolling)
  deploy. The `compose.prod.yaml` build is single-host; the `Dockerfile.prod`
  image is registry-pushable, but a published `nene2` package (instead of the
  sibling path repo) would remove the adjacent-`../NENE2` build requirement.
- TLS termination / reverse proxy config (deployment-environment specific).
