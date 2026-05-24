# Docker Development

NeNe Records runs locally with Docker Compose — PHP API, MySQL, phpMyAdmin (darkwolf theme), and Mailpit for outbound mail capture.

**Requires:** the [NENE2](https://github.com/hideyukiMORI/NENE2) path repository at `../NENE2` relative to this project (same layout as `/home/xi/docker/nene-records` + `/home/xi/docker/NENE2`).

## Requirements

- Docker
- Docker Compose v2

## Quick start

```bash
cp .env.example .env
# Edit .env: set DB_ADAPTER=mysql and Docker DB credentials (see .env.example comments)

docker compose up --build
```

Open:

| Service | URL |
| --- | --- |
| **API** | http://localhost:8080/health |
| **OpenAPI** | http://localhost:8080/openapi.php |
| **phpMyAdmin** | http://localhost:8081/ |
| **Mailpit** | http://localhost:8025/ |

Admin frontend (host, not in Compose):

```bash
npm run dev --prefix frontend
```

→ http://localhost:5173/entity-types (proxies `/api` to `:8080`)

## Default ports

| Variable | Default | Service |
| --- | --- | --- |
| `NENE_RECORDS_PORT` | `8080` | API (Apache) |
| `NENE_RECORDS_PHPMYADMIN_PORT` | `8081` | phpMyAdmin |
| `NENE_RECORDS_MYSQL_PORT` | `3307` | MySQL (host) |
| `NENE_RECORDS_MAILPIT_WEB_PORT` | `8025` | Mailpit web UI |

Change ports when another stack already uses them:

```bash
NENE_RECORDS_PORT=8082 docker compose up --build
```

## phpMyAdmin (darkwolf)

Log in with the **MySQL** development credentials from `.env`:

```text
user:     nene_records
password: nene_records
```

Root access (schema inspection only — local dev):

```text
user:     root
password: nene_records_root
```

The image bundles the [darkwolf](https://files.phpmyadmin.net/themes/darkwolf/) theme for phpMyAdmin 5.2 (NeNe pattern).

**Security:** phpMyAdmin is for **trusted local development only**. Do not expose it on production or public networks.

## Mailpit

The `mailpit` service catches SMTP on `mailpit:1025` inside the Compose network. The app container receives:

```text
MAIL_DSN=smtp://mailpit:1025
MAIL_FROM=noreply@nene-records.local
```

Inspect sent messages at http://localhost:8025/. Production and staging should use a real SMTP relay via environment variables — not Mailpit.

Mail sending in application code is planned; Compose wiring is ready for when the feature lands.

## Database

On first `docker compose up`, the `app` service runs:

1. `composer install`
2. `composer migrations:migrate`

To skip migrations (e.g. debugging):

```bash
NENE_RECORDS_SKIP_MIGRATE=1 docker compose up app
```

PHPUnit on the **host** still defaults to SQLite (see `.env.example`). Docker MySQL is for API development and manual inspection via phpMyAdmin.

## Verification

With the stack running:

```bash
curl http://localhost:8080/health
curl http://localhost:8080/api/v1/entity-types
composer check   # host PHP + SQLite — unchanged
```

Inside the app container:

```bash
docker compose exec app composer check
```

## Stop

```bash
docker compose down
```

Remove MySQL data:

```bash
docker compose down -v
```

## Related

- Backend standards: `docs/development/backend-standards.md`
- Frontend dev proxy: `frontend/README.md` (Phase 4+)
- NENE2 Docker reference: `vendor/hideyukimori/nene2/docs/development/docker.md`
