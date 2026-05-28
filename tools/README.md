# Tools

CLI utilities for local development and contract maintenance. Run from the repository root unless noted.

| Script | Purpose |
| --- | --- |
| [`generate-mcp-tools.php`](./generate-mcp-tools.php) | Regenerate `docs/mcp/tools.json` from OpenAPI-aligned definitions |
| [`validate-openapi.php`](./validate-openapi.php) | Validate `docs/openapi/openapi.yaml` |
| [`seed-blog-demo.php`](./seed-blog-demo.php) | Seed ~50 blog demo records via the HTTP API (Consumer View testing) |
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
