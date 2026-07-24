<?php

declare(strict_types=1);

/**
 * CLI daily analytics summary export (Path B / #1007, #1008).
 *
 * Writes one org's per-day access summary as JSON so an external daily report (e.g. the
 * hideyuki-mori.com mail on the same HETEML host) can fold in an "## <site>" section without
 * touching the DB. Output is aggregate-only — request counts, status distribution, popular
 * pages (SSR + LP BEACON), popular entities, and the privacy-first visitor summary. No raw
 * IP/UA/referer/query is ever read or written (ADR 0006).
 *
 * HETEML cron cannot run a bare .php; call this from a `#!/bin/sh` wrapper as
 *   /usr/local/bin/php8.4 tools/export-access-summary.php --org=<id|slug> --date=YYYY-MM-DD
 *
 * Usage:
 *   php tools/export-access-summary.php --org=ayane [--date=2026-07-24] [--out=/path/summary.json]
 *
 * Options:
 *   --org=REF    (required) organization: numeric id or slug
 *   --date=DATE  target day (YYYY-MM-DD); defaults to yesterday (server TZ)
 *   --out=PATH   output file; defaults to ~/site-logs/<slug>/summary-<date>.json
 */

use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Analytics\AccessLogRepositoryInterface;
use NeNeRecords\Analytics\VisitorSummary;
use NeNeRecords\Http\RuntimeContainerFactory;
use NeNeRecords\Organization\OrganizationRepositoryInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @param non-empty-string $name */
function summaryOption(string $name): ?string
{
    foreach ($GLOBALS['argv'] as $arg) {
        if (is_string($arg) && str_starts_with($arg, "--{$name}=")) {
            return substr($arg, strlen($name) + 3);
        }
    }

    return null;
}

$orgRef = summaryOption('org');
if ($orgRef === null || $orgRef === '') {
    fwrite(STDERR, "ERROR: --org=<id|slug> is required.\n");
    exit(1);
}

$date = summaryOption('date');
if ($date === null || $date === '') {
    // access_date is stored in UTC (the app's clock is UTC), so the default day must be
    // UTC too — otherwise the cron misaligns at the UTC/JST boundary and drops rows.
    $date = gmdate('Y-m-d', time() - 86400);
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
    fwrite(STDERR, "ERROR: --date must be YYYY-MM-DD.\n");
    exit(1);
}

$projectRoot = dirname(__DIR__);
$container = (new RuntimeContainerFactory($projectRoot))->create();

$organizations = $container->get(OrganizationRepositoryInterface::class);
$repository = $container->get(AccessLogRepositoryInterface::class);
$holder = $container->get('nene-records.org_id_holder');

if (
    !$organizations instanceof OrganizationRepositoryInterface
    || !$repository instanceof AccessLogRepositoryInterface
    || !$holder instanceof RequestScopedHolder
) {
    fwrite(STDERR, "ERROR: the application container is misconfigured.\n");
    exit(1);
}

$organization = ctype_digit($orgRef)
    ? $organizations->findById((int) $orgRef)
    : $organizations->findBySlug($orgRef);

if ($organization === null || $organization->id === null) {
    fwrite(STDERR, "ERROR: no organization found for '{$orgRef}'.\n");
    exit(1);
}

$orgId = (int) $organization->id;
$holder->set($orgId);

$day = new DateTimeImmutable($date);

$byDate = $repository->aggregateByDate($day, $day);
$totals = $byDate[0] ?? null;

$entityViews = $repository->aggregateEntityViews($date);
$popularEntities = [];
foreach (array_slice($entityViews, 0, 10, true) as $entityId => $count) {
    $popularEntities[] = ['entity_id' => $entityId, 'count' => $count];
}

$summary = $repository->aggregateVisitorSummary($day, $day, 10);

$out = [
    'schema_version' => 1,
    'site' => $organization->customDomain ?? $organization->slug,
    'org_id' => $orgId,
    'date' => $date,
    'generated_at' => date('c'),
    'totals' => [
        'request_count' => $totals !== null ? $totals->requestCount : 0,
        'avg_duration_ms' => $totals !== null ? $totals->avgDurationMs : 0.0,
        'status' => $repository->statusDistribution($day, $day),
    ],
    'popular_pages' => $repository->popularPages($day, $day, 10),
    'popular_entities' => $popularEntities,
    'visitor' => summaryHasData($summary) ? [
        'unique_visitors' => $summary->uniqueVisitors,
        'bot_rate' => $summary->botRate,
        'top_referrers' => $summary->topReferrers,
        'utm' => $summary->utm,
        'ref' => $summary->ref,
    ] : null,
    'notes' => [
        'day_basis' => 'UTC',
        'excludes' => ['/api/*', '/media/*', '/robots.txt', '/sitemap.xml'],
        'visitor_null_reason' => 'opt-in OFF or no Path B data for this range',
    ],
];

$outPath = summaryOption('out');
if ($outPath === null || $outPath === '') {
    $home = getenv('HOME') ?: $projectRoot;
    $outPath = $home . '/site-logs/' . $organization->slug . '/summary-' . $date . '.json';
}

$dir = dirname($outPath);
if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    fwrite(STDERR, "ERROR: could not create output directory '{$dir}'.\n");
    exit(1);
}

$json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false || file_put_contents($outPath, $json . "\n") === false) {
    fwrite(STDERR, "ERROR: could not write '{$outPath}'.\n");
    exit(1);
}

fwrite(STDOUT, "Wrote {$outPath}\n");

function summaryHasData(VisitorSummary $summary): bool
{
    return $summary->uniqueVisitors > 0
        || $summary->botRate !== null
        || $summary->topReferrers !== []
        || $summary->utm !== []
        || $summary->ref !== [];
}
