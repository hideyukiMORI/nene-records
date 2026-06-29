<?php

declare(strict_types=1);

/**
 * Seeds a nested custom-permalink page hierarchy (a "docs-demo" dataset) via the
 * authenticated HTTP API, so the permalink-derived directory view (admin, #654)
 * and the public breadcrumb / child-list (#653) can be exercised on demand.
 *
 * Records are created under a dedicated `docs` entity type (so the default
 * `posts` / `pages` types stay untouched) with explicit nested permalinks like
 * `/docs`, `/docs/guides/authentication`, `/company/about`, `/legal/privacy`.
 *
 * Idempotent: a record whose slug already exists is left as-is and skipped. To
 * reseed from scratch, delete the `docs` entity type in the admin first.
 *
 * Auth: writes require an admin session, so the script logs in first. Credentials
 * come from --email / --password or the NENE_INSTALL_ADMIN_* env vars.
 *
 * Usage:
 *   docker compose exec -T \
 *     -e NENE_INSTALL_ADMIN_EMAIL=admin@example.com \
 *     -e NENE_INSTALL_ADMIN_PASSWORD='change-me' \
 *     app php tools/seed-docs-demo.php http://localhost
 *
 *   php tools/seed-docs-demo.php http://localhost:18082 \
 *     --email=admin@example.com --password='change-me' [--limit=0]
 */

namespace NeNeRecords\Tools\SeedDocsDemo;

$baseUrl = 'http://localhost:8080';
$email = (string) (getenv('NENE_INSTALL_ADMIN_EMAIL') ?: 'admin@example.com');
$password = (string) (getenv('NENE_INSTALL_ADMIN_PASSWORD') ?: '');
$limit = 0;
// Min gap between API calls (ms). The API rate-limits at 120 req / 60s; ~500ms
// keeps a comfortable margin (429s are also retried, see request()).
$minDelayMs = 500;

foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--email=(.*)$/', $arg, $m) === 1) {
        $email = $m[1];
    } elseif (preg_match('/^--password=(.*)$/', $arg, $m) === 1) {
        $password = $m[1];
    } elseif (preg_match('/^--limit=(\d+)$/', $arg, $m) === 1) {
        $limit = (int) $m[1];
    } elseif (preg_match('/^--delay=(\d+)$/', $arg, $m) === 1) {
        $minDelayMs = (int) $m[1];
    } elseif (!str_starts_with($arg, '--')) {
        $baseUrl = $arg;
    }
}

$baseUrl = rtrim($baseUrl, '/');

if ($password === '') {
    fwrite(STDERR, "Admin password required: pass --password=... or NENE_INSTALL_ADMIN_PASSWORD env.\n");
    exit(1);
}

/** Bearer token, set after login. */
$authToken = '';
/** Timestamp (microtime) of the last API call, for inter-request pacing. */
$lastCallMicros = 0.0;

/**
 * @param non-empty-string $method
 * @param array<string, mixed>|null $body
 * @return array{status: int, body: array<string, mixed>}
 */
function request(string $method, string $path, ?array $body = null): array
{
    global $baseUrl, $authToken, $minDelayMs, $lastCallMicros;

    for ($attempt = 0; ; ++$attempt) {
        // Pace: keep a minimum gap between calls to respect the API rate limit.
        $gap = $minDelayMs / 1000;
        $elapsed = microtime(true) - $lastCallMicros;
        if ($elapsed < $gap) {
            usleep((int) (($gap - $elapsed) * 1_000_000));
        }
        $lastCallMicros = microtime(true);

        $ch = curl_init($baseUrl . $path);
        if ($ch === false) {
            throw new \RuntimeException('Failed to initialize cURL.');
        }

        $headers = ['Accept: application/json', 'Content-Type: application/json', 'X-Requested-With: XMLHttpRequest'];
        if ($authToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $authToken;
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        }

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!is_string($raw)) {
            throw new \RuntimeException(sprintf('Transport error: %s %s — %s', $method, $path, $error));
        }

        // Rate limited: back off for the suggested window and retry.
        if ($status === 429 && $attempt < 5) {
            $retry = preg_match('/in (\d+) second/', $raw, $rm) === 1 ? min((int) $rm[1] + 1, 65) : 5;
            fwrite(STDERR, sprintf("  rate limited — waiting %ds…\n", $retry));
            sleep($retry);

            continue;
        }

        $decoded = $raw === '' ? [] : json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            $decoded = ['_raw' => $raw];
        }

        /** @var array<string, mixed> $decoded */
        return ['status' => $status, 'body' => $decoded];
    }
}

/**
 * Call and throw on any >= 400 response.
 *
 * @param non-empty-string $method
 * @param array<string, mixed>|null $body
 * @return array<string, mixed>
 */
function api(string $method, string $path, ?array $body = null): array
{
    $res = request($method, $path, $body);
    if ($res['status'] >= 400) {
        $detail = is_string($res['body']['detail'] ?? null)
            ? $res['body']['detail']
            : (string) json_encode($res['body']);
        throw new \RuntimeException(sprintf('API error %d on %s %s: %s', $res['status'], $method, $path, $detail));
    }

    return $res['body'];
}

function login(string $email, string $password): void
{
    global $authToken;

    $res = request('POST', '/api/v1/auth/login', ['email' => $email, 'password' => $password]);
    $token = $res['body']['token'] ?? null;
    if ($res['status'] !== 200 || !is_string($token)) {
        throw new \RuntimeException('Login failed: ' . (string) json_encode($res['body']));
    }
    $authToken = $token;
}

/** @return array<string, mixed> */
function ensureEntityType(string $slug, string $name): array
{
    $list = api('GET', '/api/v1/entity-types?limit=100&offset=0');
    foreach (is_array($list['items'] ?? null) ? $list['items'] : [] as $item) {
        if (is_array($item) && ($item['slug'] ?? null) === $slug) {
            echo sprintf("Entity type %s already exists (id=%d)\n", $slug, (int) $item['id']);

            return $item;
        }
    }

    $created = api('POST', '/api/v1/entity-types', ['name' => $name, 'slug' => $slug, 'is_pinned' => true]);
    echo sprintf("Created entity type %s (id=%d)\n", $slug, (int) $created['id']);

    return $created;
}

function ensureFieldDef(int $entityTypeId, string $fieldKey, string $dataType): void
{
    $list = api('GET', sprintf('/api/v1/field-defs?entity_type_id=%d&limit=100&offset=0', $entityTypeId));
    foreach (is_array($list['items'] ?? null) ? $list['items'] : [] as $item) {
        if (is_array($item) && ($item['field_key'] ?? null) === $fieldKey) {
            return;
        }
    }

    api('POST', '/api/v1/field-defs', [
        'entity_type_id' => $entityTypeId,
        'field_key' => $fieldKey,
        'data_type' => $dataType,
    ]);
    echo sprintf("  registered field_def %s (%s)\n", $fieldKey, $dataType);
}

/**
 * Page through every entity of the type and return a slug => id map. Used to
 * skip records that already exist, so reseeding is idempotent.
 *
 * @return array<string, int>
 */
function loadExistingSlugs(int $entityTypeId): array
{
    $bySlug = [];
    $offset = 0;
    do {
        $page = api('GET', sprintf('/api/v1/entities?entity_type_id=%d&limit=100&offset=%d', $entityTypeId, $offset));
        $items = is_array($page['items'] ?? null) ? $page['items'] : [];
        foreach ($items as $item) {
            if (is_array($item) && is_string($item['slug'] ?? null)) {
                $bySlug[$item['slug']] = (int) $item['id'];
            }
        }
        $offset += 100;
    } while (count($items) === 100);

    return $bySlug;
}

/** Create a draft entity and return its id. */
function createEntity(int $entityTypeId, string $slug): int
{
    $created = api('POST', '/api/v1/entities', [
        'entity_type_id' => $entityTypeId,
        'slug' => $slug,
        'status' => 'draft',
    ]);

    return (int) $created['id'];
}

function setTextField(int $entityId, string $fieldKey, string $value): void
{
    api('POST', '/api/v1/text-fields', [
        'entity_id' => $entityId,
        'field_key' => $fieldKey,
        'value' => $value,
    ]);
}

function publish(int $entityId, int $entityTypeId, string $slug, string $permalink, string $title): void
{
    api('PUT', sprintf('/api/v1/entities/%d', $entityId), [
        'entity_type_id' => $entityTypeId,
        'slug' => $slug,
        'status' => 'published',
        'permalink' => $permalink,
        'meta_title' => $title,
        'meta_description' => sprintf('%s — NeNe Records docs-demo page at %s', $title, $permalink),
    ]);
}

function pageBody(string $permalink, string $title): string
{
    return <<<TEXT
        # {$title}

        This is the **{$title}** page, published at `{$permalink}`.

        It is a demo record created by `tools/seed-docs-demo.php` to exercise the
        permalink-derived directory view (admin) and the public breadcrumb /
        child-list (#651). The path segments form the folder hierarchy; this
        record's place in the tree is derived purely from its permalink.
        TEXT;
}

/**
 * The demo hierarchy: permalink path => page title. Parents are listed before
 * children so the directory tree reads top-down. Each entry becomes one
 * published record whose slug is the path with `/` replaced by `-`.
 *
 * @return array<string, string>
 */
function demoPages(): array
{
    return [
        // --- Documentation ---
        '/docs' => 'Documentation',
        '/docs/getting-started' => 'Getting Started',
        '/docs/getting-started/installation' => 'Installation',
        '/docs/getting-started/requirements' => 'Requirements',
        '/docs/getting-started/quickstart' => 'Quickstart',
        '/docs/getting-started/configuration' => 'Configuration',
        '/docs/getting-started/upgrading' => 'Upgrading',
        '/docs/guides' => 'Guides',
        '/docs/guides/authentication' => 'Authentication',
        '/docs/guides/permalinks' => 'Permalinks & URLs',
        '/docs/guides/directory-view' => 'Directory View',
        '/docs/guides/blocks' => 'Block Editor',
        '/docs/guides/media' => 'Media & Images',
        '/docs/guides/seo' => 'SEO & Metadata',
        '/docs/guides/ssr' => 'Server-Side Rendering',
        '/docs/guides/theming' => 'Theming',
        '/docs/guides/multi-tenant' => 'Multi-Tenant',
        '/docs/guides/migrations' => 'Database Migrations',
        '/docs/guides/importing' => 'Importing from WordPress',
        '/docs/guides/custom-domains' => 'Custom Domains',
        '/docs/guides/caching' => 'Caching',
        '/docs/guides/backups' => 'Backups',
        '/docs/api' => 'API Reference',
        '/docs/api/overview' => 'Overview',
        '/docs/api/authentication' => 'Authentication',
        '/docs/api/entities' => 'Entities',
        '/docs/api/entity-types' => 'Entity Types',
        '/docs/api/field-defs' => 'Field Definitions',
        '/docs/api/text-fields' => 'Text Fields',
        '/docs/api/tags' => 'Tags',
        '/docs/api/relations' => 'Relations',
        '/docs/api/errors' => 'Errors',
        '/docs/api/pagination' => 'Pagination',
        '/docs/api/rate-limits' => 'Rate Limits',
        '/docs/tutorials' => 'Tutorials',
        '/docs/tutorials/first-record' => 'Your First Record',
        '/docs/tutorials/custom-permalinks' => 'Custom Permalinks',
        '/docs/tutorials/build-a-blog' => 'Build a Blog',
        '/docs/tutorials/build-a-docs-site' => 'Build a Docs Site',
        '/docs/tutorials/wordpress-migration' => 'WordPress Migration',
        '/docs/tutorials/mcp-tools' => 'Using MCP Tools',
        '/docs/tutorials/openapi-codegen' => 'OpenAPI Codegen',
        '/docs/concepts' => 'Concepts',
        '/docs/concepts/entities' => 'Entities & Records',
        '/docs/concepts/typed-fields' => 'Typed Fields',
        '/docs/concepts/organizations' => 'Organizations',
        '/docs/concepts/permalinks' => 'Permalink Model',
        '/docs/concepts/rendering' => 'Rendering Pipeline',
        '/docs/concepts/security' => 'Security Model',
        '/docs/reference' => 'Reference',
        '/docs/reference/cli' => 'CLI Commands',
        '/docs/reference/env-vars' => 'Environment Variables',
        '/docs/reference/ports' => 'Ports',
        '/docs/reference/openapi' => 'OpenAPI Spec',
        '/docs/reference/mcp-catalog' => 'MCP Tool Catalog',
        '/docs/reference/changelog' => 'Changelog',
        // --- Company ---
        '/company' => 'Company',
        '/company/about' => 'About Us',
        '/company/team' => 'Team',
        '/company/contact' => 'Contact',
        '/company/press' => 'Press',
        '/company/careers' => 'Careers',
        '/company/careers/engineering' => 'Engineering',
        '/company/careers/design' => 'Design',
        '/company/careers/sales' => 'Sales',
        '/company/careers/support' => 'Support',
        // --- Legal ---
        '/legal' => 'Legal',
        '/legal/privacy' => 'Privacy Policy',
        '/legal/terms' => 'Terms of Service',
        '/legal/cookies' => 'Cookie Policy',
        '/legal/dpa' => 'Data Processing Addendum',
        '/legal/sla' => 'Service Level Agreement',
        // --- Support ---
        '/support' => 'Support',
        '/support/faq' => 'FAQ',
        '/support/contact' => 'Contact Support',
        '/support/status' => 'System Status',
        '/support/changelog' => 'Release Notes',
        '/support/troubleshooting' => 'Troubleshooting',
        '/support/troubleshooting/login-issues' => 'Login Issues',
        '/support/troubleshooting/email-delivery' => 'Email Delivery',
        '/support/troubleshooting/permalink-404' => 'Permalink 404s',
        '/support/troubleshooting/performance' => 'Performance',
        // --- Resources ---
        '/resources' => 'Resources',
        '/resources/templates' => 'Templates',
        '/resources/examples' => 'Examples',
        '/resources/downloads' => 'Downloads',
        '/resources/integrations' => 'Integrations',
        '/resources/community' => 'Community',
        '/resources/roadmap' => 'Roadmap',
    ];
}

try {
    $health = api('GET', '/health');
    echo sprintf("API ok: %s\n", is_string($health['service'] ?? null) ? $health['service'] : 'unknown');

    login($email, $password);
    echo sprintf("Logged in as %s\n", $email);

    $type = ensureEntityType('docs', 'Docs');
    $typeId = (int) $type['id'];

    echo "Registering field definitions…\n";
    ensureFieldDef($typeId, 'title', 'text');
    ensureFieldDef($typeId, 'body', 'text');

    $pages = demoPages();
    if ($limit > 0) {
        $pages = array_slice($pages, 0, $limit, true);
    }
    $existing = loadExistingSlugs($typeId);
    echo sprintf("Seeding %d docs-demo pages (%d already present)…\n", count($pages), count($existing));

    $created = 0;
    $skipped = 0;
    $i = 0;
    foreach ($pages as $permalink => $title) {
        ++$i;
        $slug = str_replace('/', '-', ltrim($permalink, '/'));

        if (isset($existing[$slug])) {
            ++$skipped;

            continue;
        }

        $entityId = createEntity($typeId, $slug);
        setTextField($entityId, 'title', $title);
        setTextField($entityId, 'body', pageBody($permalink, $title));
        publish($entityId, $typeId, $slug, $permalink, $title);
        ++$created;

        if ($i % 20 === 0 || $i === count($pages)) {
            echo sprintf("  … %d/%d processed (%d created, %d existing)\n", $i, count($pages), $created, $skipped);
        }
    }

    echo sprintf("\nDone. %d created, %d already existed.\n", $created, $skipped);
    echo "Admin directory view: open the Docs content type → Directory tab.\n";
    echo "Public sample: /docs/guides/authentication (breadcrumb + child list under /docs/guides).\n";
} catch (\Throwable $exception) {
    fwrite(STDERR, 'Seed failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
