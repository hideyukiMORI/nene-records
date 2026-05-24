<?php

declare(strict_types=1);

/**
 * Seeds ~50 blog-like demo records via the public HTTP API.
 *
 * Idempotent: skips creation when the blog entity type already has enough posts.
 *
 * Usage:
 *   php tools/seed-blog-demo.php [base_url]
 *
 * Examples:
 *   php tools/seed-blog-demo.php
 *   php tools/seed-blog-demo.php http://localhost:8080
 *   docker compose exec app php tools/seed-blog-demo.php http://localhost
 */

$baseUrl = rtrim($argv[1] ?? 'http://localhost:8080', '/');
$postCount = 50;

/**
 * @param array<string, mixed>|null $body
 * @return array<string, mixed>
 */
/**
 * @param non-empty-string $method
 * @param array<string, mixed>|null $body
 * @return array<string, mixed>
 */
function api(string $method, string $path, ?array $body = null): array
{
    global $baseUrl;

    $url = $baseUrl . $path;
    $ch = curl_init($url);

    if ($ch === false) {
        throw new RuntimeException('Failed to initialize cURL.');
    }

    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($body !== null) {
        $json = json_encode($body, JSON_THROW_ON_ERROR);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    }

    $raw = curl_exec($ch);

    if ($raw === false) {
        throw new RuntimeException(sprintf('Request failed: %s %s — %s', $method, $path, curl_error($ch)));
    }

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($raw) || $raw === '') {
        if ($status >= 200 && $status < 300) {
            return [];
        }

        throw new RuntimeException(sprintf('Empty response: %s %s (HTTP %d)', $method, $path, $status));
    }

    /** @var array<string, mixed> $payload */
    $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

    if ($status >= 400) {
        $detail = is_string($payload['detail'] ?? null) ? $payload['detail'] : json_encode($payload);
        throw new RuntimeException(sprintf('API error %d on %s %s: %s', $status, $method, $path, $detail));
    }

    return $payload;
}

/** @return array<string, mixed>|null */
function findEntityTypeBySlug(string $slug): ?array
{
    $list = api('GET', '/api/v1/entity-types?limit=100&offset=0');

    foreach ($list['items'] as $item) {
        if (is_array($item) && ($item['slug'] ?? '') === $slug) {
            return $item;
        }
    }

    return null;
}

/** @return array<string, mixed> */
function ensureEntityType(string $slug, string $name): array
{
    $existing = findEntityTypeBySlug($slug);

    if ($existing !== null) {
        echo sprintf("Entity type %s already exists (id=%d)\n", $slug, (int) $existing['id']);

        return $existing;
    }

    $created = api('POST', '/api/v1/entity-types', ['name' => $name, 'slug' => $slug]);
    echo sprintf("Created entity type %s (id=%d)\n", $slug, (int) $created['id']);

    return $created;
}

function ensureFieldDef(int $entityTypeId, string $fieldKey, string $dataType): void
{
    $list = api('GET', sprintf('/api/v1/field-defs?entity_type_id=%d&limit=100&offset=0', $entityTypeId));

    foreach ($list['items'] as $item) {
        if (is_array($item) && ($item['field_key'] ?? '') === $fieldKey) {
            echo sprintf("  field_def %s already registered\n", $fieldKey);

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
 * @param array<string, string> $tags slug => display name
 * @return array<string, int> slug => id
 */
function ensureTags(array $tags): array
{
    $existing = api('GET', '/api/v1/tags?limit=100&offset=0');
    $bySlug = [];

    foreach ($existing['items'] as $item) {
        if (is_array($item) && is_string($item['slug'] ?? null)) {
            $bySlug[$item['slug']] = (int) $item['id'];
        }
    }

    foreach ($tags as $slug => $name) {
        if (isset($bySlug[$slug])) {
            continue;
        }

        $created = api('POST', '/api/v1/tags', ['slug' => $slug, 'name' => $name]);
        $bySlug[$slug] = (int) $created['id'];
        echo sprintf("Created tag %s (id=%d)\n", $slug, $bySlug[$slug]);
    }

    return $bySlug;
}

/** @return list<string> */
function blogTitles(): array
{
    return [
        'First day trying NeNe Records',
        'What does API first buy you?',
        'Typed fields vs WordPress meta',
        'Creating an entity type in the admin UI',
        'Viewing Consumer Views on a public URL',
        'Fetching lists quickly with TanStack Query',
        'Filtering posts by tag',
        'Linking authors with relation fields',
        'How OpenAPI spawns MCP tools',
        'Checking page views with the access log API',
        'Looking back after Phase 3',
        'The pain and joy of React + TypeScript strict',
        'An afternoon watching Button in Storybook',
        'Why MSW tests feel reassuring',
        'Starting the API with docker compose',
        'When to use MySQL vs SQLite',
        'Why field_defs come first',
        'Handling soft-deleted records',
        'Slugs should stay kebab-case',
        'Problem Details make errors readable',
        'Calling listEntities from MCP',
        'A future where AI handles CRUD',
        'Notes on entity platform design',
        'Using NENE2 as a consumer runtime',
        'Benefits of issue-driven development',
        'Keeping Conventional Commits going',
        'Checklists for PR review',
        'Theme token cleanup on the frontend',
        'Trying the consumer-brand theme',
        'The /view index page landed',
        'Public list pagination',
        'Relation links on record detail',
        'Inverse relations for backlinks',
        'The boundary between Admin and Consumer',
        'Tag filter UI on the entities list',
        'Attach and detach tags on record detail',
        'Tag CRUD from the admin app',
        'Designing enum field options',
        'Store datetime values as ISO8601',
        'Formatting bool fields for display',
        'Managing sort order with int fields',
        'Filtering text-fields by entity_type_id',
        'What comes next for the schema registry',
        'Seeding dummy data for a public site',
        'Checking a blog-like layout',
        'Trying pagination with 50 records',
        'Showing only featured-tagged posts',
        'Dev diary: hitting the API again today',
        'Pair programming notes',
        'Weekend hack: Consumer Views',
        'Finished reading the product vision doc',
    ];
}

function blogExcerpt(int $index): string
{
    $samples = [
        'Exercising public list and detail views while keeping the API boundary clean.',
        'A small experiment building article data with typed fields.',
        'Notes from trying NeNe Records Consumer Views.',
        'Creating data through the HTTP API only — no admin UI clicks.',
        'Dummy posts with tags for slightly realistic browsing.',
    ];

    return $samples[$index % count($samples)];
}

function blogBody(int $index, string $title): string
{
    return <<<MD
## {$title}

This is demo blog post #{$index}. It was seeded so you can verify **Consumer Views** at `/view/blog` — list, detail, tags, and pagination.

### About this post

- Title, excerpt, body, and published_at are stored as typed fields
- The public SPA reads the same JSON API as any external consumer
- Tags such as tech and diary support category-style filtering

### Sample body

Start the API, open `/view/blog`, scan the card list, and click through to detail. If relations or tags are present, follow those links too. This dataset is a small preview of that workflow.

> Records were created via `tools/seed-blog-demo.php` using the HTTP API only.

MD;
}

try {
    $health = api('GET', '/health');
    echo sprintf("API ok: %s\n", is_string($health['service'] ?? null) ? $health['service'] : 'unknown');

    $entityType = ensureEntityType('blog', 'Blog');
    $entityTypeId = (int) $entityType['id'];

    echo "Registering field definitions…\n";
    ensureFieldDef($entityTypeId, 'title', 'text');
    ensureFieldDef($entityTypeId, 'excerpt', 'text');
    ensureFieldDef($entityTypeId, 'body', 'text');
    ensureFieldDef($entityTypeId, 'published_at', 'datetime');

    $tagIds = ensureTags([
        'tech' => 'Technology',
        'diary' => 'Diary',
        'news' => 'News',
        'featured' => 'Featured',
        'devlog' => 'Dev Log',
    ]);

    $existing = api('GET', sprintf('/api/v1/entities?entity_type_id=%d&limit=1&offset=0', $entityTypeId));
    $existingTotal = (int) ($existing['total'] ?? 0);

    if ($existingTotal >= $postCount) {
        echo sprintf("Already have %d blog entities — skipping create.\n", $existingTotal);
        echo "Public browse: http://localhost:5173/view/blog\n";

        exit(0);
    }

    $titles = blogTitles();
    $toCreate = $postCount - $existingTotal;
    echo sprintf("Creating %d blog posts…\n", $toCreate);

    $tagSlugs = array_keys($tagIds);

    for ($i = 0; $i < $toCreate; ++$i) {
        $n = $existingTotal + $i + 1;
        $title = $titles[($n - 1) % count($titles)] . sprintf(' #%d', $n);

        $entity = api('POST', '/api/v1/entities', ['entity_type_id' => $entityTypeId]);
        $entityId = (int) $entity['id'];

        api('POST', '/api/v1/text-fields', [
            'entity_id' => $entityId,
            'field_key' => 'title',
            'value' => $title,
        ]);
        api('POST', '/api/v1/text-fields', [
            'entity_id' => $entityId,
            'field_key' => 'excerpt',
            'value' => blogExcerpt($n),
        ]);
        api('POST', '/api/v1/text-fields', [
            'entity_id' => $entityId,
            'field_key' => 'body',
            'value' => blogBody($n, $title),
        ]);

        $daysAgo = ($n * 3) % 120;
        $publishedAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->modify(sprintf('-%d days', $daysAgo))
            ->setTime(9 + ($n % 10), ($n * 7) % 60)
            ->format(DateTimeInterface::ATOM);

        api('POST', '/api/v1/datetime-fields', [
            'entity_id' => $entityId,
            'field_key' => 'published_at',
            'value' => $publishedAt,
        ]);

        $assignedTags = array_values(array_unique([
            $tagSlugs[$n % count($tagSlugs)],
            $tagSlugs[($n + 2) % count($tagSlugs)],
        ]));

        if ($n % 5 === 0) {
            $assignedTags[] = 'featured';
        }

        foreach (array_unique($assignedTags) as $tagSlug) {
            api('POST', sprintf('/api/v1/entities/%d/tags', $entityId), [
                'tag_id' => $tagIds[$tagSlug],
            ]);
        }

        if ($n % 10 === 0 || $n === $toCreate) {
            echo sprintf("  … %d posts created\n", $n);
        }
    }

    $final = api('GET', sprintf('/api/v1/entities?entity_type_id=%d&limit=1&offset=0', $entityTypeId));
    echo sprintf("\nDone. blog entity count: %d\n", (int) ($final['total'] ?? 0));
    echo "Browse at: http://localhost:5173/view/blog\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Seed failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
