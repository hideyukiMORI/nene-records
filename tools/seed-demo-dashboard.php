<?php

declare(strict_types=1);

/**
 * Seeds a realistic volume of demo content + access logs so the admin
 * dashboard and content lists have something substantial to show locally.
 *
 * Unlike {@see seed-blog-demo.php} (which drives the public HTTP API and needs
 * the legacy unauthenticated endpoints), this writes straight to the database
 * via PDO using the app's own ConfigLoader — so it works regardless of auth.
 *
 * What it inserts, for the target organization:
 *   - For each of the org's content types: a batch of published + draft
 *     entities, each with `title` and `body` text fields.
 *   - One month of access-log rows (heavier today) for the access counters.
 *
 * Idempotent: removes its own prior rows (entity slug prefix "demo-",
 * access-log request_id prefix "demo-") before re-inserting.
 *
 * Usage:
 *   docker compose exec app php tools/seed-demo-dashboard.php
 *   docker compose exec app php tools/seed-demo-dashboard.php --org=1
 */

use Nene2\Config\ConfigLoader;

require_once __DIR__ . '/../vendor/autoload.php';

const SLUG_PREFIX = 'demo-';
const REQUEST_ID_PREFIX = 'demo-';

/** How many published + draft entities to create per content type. */
const PER_TYPE_PUBLISHED = 24;
const PER_TYPE_DRAFT = 6;

/**
 * @return list<string>
 */
function demoTitles(): array
{
    return [
        'First day with NeNe Records', 'API-first, explained', 'Typed fields vs post meta',
        'Creating an entity type', 'Public Consumer Views', 'Lists with TanStack Query',
        'Filtering posts by tag', 'Author relation fields', 'OpenAPI spawns MCP tools',
        'Reading the access log', 'Looking back at Phase 3', 'React + TypeScript strict',
        'Button in Storybook', 'Why MSW tests reassure', 'Starting with docker compose',
        'MySQL vs SQLite', 'Field defs come first', 'Soft-deleted records',
        'Slugs stay kebab-case', 'Problem Details errors', 'listEntities from MCP',
        'AI handles CRUD', 'Entity platform design', 'NENE2 as a runtime',
        'Issue-driven development', 'Conventional Commits', 'Checklists for PR review',
        'Theme token cleanup', 'The consumer-brand theme', 'The /view index landed',
        'Public list pagination', 'Relation links on detail', 'Inverse backlinks',
        'Admin vs Consumer boundary', 'Tag filter UI', 'Attach and detach tags',
    ];
}

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = (string) preg_replace('/[^a-z0-9]+/', '-', $s);

    return trim($s, '-');
}

/**
 * @return array{0: int, 1: int} [entitiesCreated, accessLogsCreated]
 */
function seed(PDO $pdo, int $orgId): array
{
    // ── Resolve the org's content types ───────────────────────────────────
    $typeStmt = $pdo->prepare(
        'SELECT id, slug FROM entity_types WHERE organization_id = :org ORDER BY id',
    );
    $typeStmt->execute([':org' => $orgId]);
    /** @var list<array{id: int, slug: string}> $types */
    $types = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($types === []) {
        throw new RuntimeException(sprintf('No entity types found for organization %d.', $orgId));
    }

    // ── Clear prior demo rows (idempotent) ─────────────────────────────────
    $idStmt = $pdo->prepare(
        'SELECT id FROM entities WHERE organization_id = :org AND slug LIKE :p',
    );
    $idStmt->execute([':org' => $orgId, ':p' => SLUG_PREFIX . '%']);
    $priorIds = $idStmt->fetchAll(PDO::FETCH_COLUMN);

    if ($priorIds !== []) {
        $in = implode(',', array_fill(0, count($priorIds), '?'));
        $pdo->prepare("DELETE FROM text_fields WHERE entity_id IN ($in)")->execute($priorIds);
        $pdo->prepare("DELETE FROM datetime_fields WHERE entity_id IN ($in)")->execute($priorIds);
        $pdo->prepare("DELETE FROM entities WHERE id IN ($in)")->execute($priorIds);
    }
    $pdo->prepare('DELETE FROM access_logs WHERE request_id LIKE :p')
        ->execute([':p' => REQUEST_ID_PREFIX . '%']);

    $titles = demoTitles();
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

    $insertEntity = $pdo->prepare(
        'INSERT INTO entities
           (organization_id, entity_type_id, slug, status, published_at, created_at, updated_at, is_deleted)
         VALUES (:org, :type, :slug, :status, :published_at, :created_at, :updated_at, 0)',
    );
    $insertText = $pdo->prepare(
        'INSERT INTO text_fields (organization_id, entity_id, field_key, locale, value, is_deleted)
         VALUES (:org, :entity, :key, NULL, :value, 0)',
    );

    $entitiesCreated = 0;

    foreach ($types as $type) {
        $typeId = (int) $type['id'];
        $typeSlug = (string) $type['slug'];
        $total = PER_TYPE_PUBLISHED + PER_TYPE_DRAFT;

        for ($i = 0; $i < $total; ++$i) {
            $isPublished = $i < PER_TYPE_PUBLISHED;
            $title = $titles[$i] ?? sprintf('%s #%d', $typeSlug, $i + 1);
            $slug = sprintf('%s%s-%s-%d', SLUG_PREFIX, $typeSlug, slugify($title), $i + 1);

            // Newest first, ~2-day cadence for the published timeline.
            $publishedAt = $isPublished ? $now->modify(sprintf('-%d days', $i * 2)) : null;
            $createdAt = $publishedAt ?? $now->modify(sprintf('-%d days', $i));

            $insertEntity->execute([
                ':org' => $orgId,
                ':type' => $typeId,
                ':slug' => $slug,
                ':status' => $isPublished ? 'published' : 'draft',
                ':published_at' => $publishedAt?->format('Y-m-d H:i:s'),
                ':created_at' => $createdAt->format('Y-m-d H:i:s'),
                ':updated_at' => $createdAt->format('Y-m-d H:i:s'),
            ]);
            $entityId = (int) $pdo->lastInsertId();

            $insertText->execute([
                ':org' => $orgId, ':entity' => $entityId, ':key' => 'title', ':value' => $title,
            ]);
            $insertText->execute([
                ':org' => $orgId,
                ':entity' => $entityId,
                ':key' => 'body',
                ':value' => sprintf("# %s\n\nDemo content for **%s**. Seeded for local preview.", $title, $title),
            ]);

            ++$entitiesCreated;
        }
    }

    $accessLogsCreated = seedAccessLogs($pdo, $orgId, $now);

    return [$entitiesCreated, $accessLogsCreated];
}

function seedAccessLogs(PDO $pdo, int $orgId, DateTimeImmutable $now): int
{
    $paths = [
        '/api/v1/entities', '/api/v1/entity-types', '/api/v1/dashboard',
        '/api/v1/media', '/api/v1/tags', '/', '/blog',
    ];
    $year = (int) $now->format('Y');
    $month = (int) $now->format('n');
    $today = (int) $now->format('j');

    $insert = $pdo->prepare(
        'INSERT INTO access_logs
           (organization_id, request_id, method, path, status_code, duration_ms, accessed_at, access_date)
         VALUES (:org, :rid, :method, :path, :status, :dur, :at, :date)',
    );

    $n = 0;
    for ($day = 1; $day <= $today; ++$day) {
        // Today gets a bigger burst; earlier days a moderate spread.
        $count = $day === $today ? random_int(90, 140) : random_int(20, 60);
        for ($k = 0; $k < $count; ++$k) {
            $ts = (new DateTimeImmutable(
                sprintf('%04d-%02d-%02d', $year, $month, $day),
                new DateTimeZone('UTC'),
            ))->setTime(random_int(0, 23), random_int(0, 59), random_int(0, 59));

            $insert->execute([
                ':org' => $orgId,
                ':rid' => REQUEST_ID_PREFIX . $n,
                ':method' => 'GET',
                ':path' => $paths[array_rand($paths)],
                ':status' => 200,
                ':dur' => random_int(2, 180) + (random_int(0, 999) / 1000),
                ':at' => $ts->format('Y-m-d H:i:s'),
                ':date' => $ts->format('Y-m-d'),
            ]);
            ++$n;
        }
    }

    return $n;
}

// ── Entry point ────────────────────────────────────────────────────────────
$orgId = 1;
foreach ($argv as $arg) {
    if (preg_match('/^--org=(\d+)$/', $arg, $m) === 1) {
        $orgId = (int) $m[1];
    }
}

try {
    $database = (new ConfigLoader(__DIR__ . '/..'))->load()->database;

    if ($database->usesUrl()) {
        $dsn = (string) $database->url;
        $pdo = new PDO($dsn);
    } else {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $database->adapter,
            $database->host,
            $database->port,
            $database->name,
            $database->charset,
        );
        $pdo = new PDO($dsn, $database->user, $database->password);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();
    [$entities, $logs] = seed($pdo, $orgId);
    $pdo->commit();

    echo sprintf(
        "Seeded %d demo entities and %d access-log rows for organization %d.\n",
        $entities,
        $logs,
        $orgId,
    );
    echo "Dashboard: http://localhost:5173/admin\n";
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Seed failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
