# Tools

CLI utilities for local development and contract maintenance. Run from the repository root unless noted.

| Script | Purpose |
| --- | --- |
| [`generate-mcp-tools.php`](./generate-mcp-tools.php) | Regenerate `docs/mcp/tools.json` from OpenAPI-aligned definitions |
| [`validate-openapi.php`](./validate-openapi.php) | Validate `docs/openapi/openapi.yaml` |
| [`seed-blog-demo.php`](./seed-blog-demo.php) | Seed ~50 blog demo records via the HTTP API (Consumer View testing) |
| [`seed-docs-demo.php`](./seed-docs-demo.php) | Seed a nested custom-permalink page hierarchy (`docs`-type) for the directory view / breadcrumb demo (#651) |
| [`webhook-worker.php`](./webhook-worker.php) | Drain the `webhook_deliveries` queue — send due deliveries with retry/backoff (#285) |

## Webhook delivery worker

Webhook dispatch is asynchronous (#285): triggering an entity change enqueues delivery rows;
this worker performs the actual HTTP sends, retrying with exponential backoff and marking
deliveries failed once attempts are exhausted. Run it on a schedule (e.g. cron every minute).

**Requirements:** database reachable, cURL extension enabled.

```bash
# Process up to 50 due deliveries once (cron-friendly)
php tools/webhook-worker.php

# Process more per run, or run continuously
php tools/webhook-worker.php --limit=200
php tools/webhook-worker.php --loop --interval=10

# Inside the app container
docker compose exec app php tools/webhook-worker.php
```

## Demo blog seed

Creates entity type `blog`, field definitions, tags, and up to 50 posts through the public CRUD API — no direct database access.

**Requirements:** API running (e.g. `docker compose up`), cURL extension enabled.

```bash
# Host (API on :8080)
php tools/seed-blog-demo.php

# Custom base URL
php tools/seed-blog-demo.php http://localhost:8080

# Inside the app container
docker compose exec app php tools/seed-blog-demo.php http://localhost
```

Then open:

- Consumer list: http://localhost:5173/view/blog
- SSR detail (when enabled): http://localhost:8080/view/blog/{id}

The script is idempotent: if `blog` already has 50 or more entities, it skips creation.

## Docs-demo hierarchy seed

Creates a dedicated `docs` entity type and ~90 published records with **nested custom permalinks** (`/docs`, `/docs/guides/authentication`, `/company/about`, `/legal/privacy`, …) so the permalink-derived **directory view** (admin) and the public **breadcrumb / child-list** (#651) have realistic data to render. The default `posts` / `pages` types are left untouched.

**Requirements:** API running, an admin account (writes require auth), cURL extension enabled.

```bash
# Inside the app container (admin creds via -e)
docker compose exec -T \
  -e NENE_INSTALL_ADMIN_EMAIL=admin@example.com \
  -e NENE_INSTALL_ADMIN_PASSWORD='change-me' \
  app php tools/seed-docs-demo.php http://localhost

# Host, explicit flags
php tools/seed-docs-demo.php http://localhost:18082 \
  --email=admin@example.com --password='change-me' [--limit=0] [--delay=500]
```

Calls are paced (`--delay`, ms) to stay under the API rate limit, and 429s are retried. Idempotent: records whose slug already exists are skipped, so reruns are safe. To reseed from scratch, delete the `docs` entity type in the admin first.

Then check:

- Admin: the **Docs** content type → **Directory** tab shows the folder tree.
- Public: `/docs/guides/authentication` renders breadcrumb + JSON-LD; `/docs/guides` lists its children.
