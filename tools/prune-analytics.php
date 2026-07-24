<?php

declare(strict_types=1);

/**
 * CLI analytics retention prune (Path B / ADR 0006 D6).
 *
 * Enforces the visitor-data retention window: drops daily salts older than the cutoff (after
 * which past visitor_hashes are permanently unlinkable) and NULLs the visitor-derived columns
 * on old access_logs rows. The anonymous base rows (method/path/status/duration) are kept, so
 * long-term traffic counts survive. Global (all orgs) — retention is an operator concern.
 *
 * HETEML cron: call from a `#!/bin/sh` wrapper as
 *   /usr/local/bin/php8.4 tools/prune-analytics.php --days=180
 *
 * Options:
 *   --days=N   retention window in days (default 180)
 */

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeNeRecords\Analytics\AnalyticsSaltRepositoryInterface;
use NeNeRecords\Http\RuntimeContainerFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$days = 180;
foreach ($GLOBALS['argv'] as $arg) {
    if (is_string($arg) && str_starts_with($arg, '--days=')) {
        $parsed = (int) substr($arg, 7);
        if ($parsed > 0) {
            $days = $parsed;
        }
    }
}

$container = (new RuntimeContainerFactory(dirname(__DIR__)))->create();

$salts = $container->get(AnalyticsSaltRepositoryInterface::class);
$query = $container->get(DatabaseQueryExecutorInterface::class);

if (
    !$salts instanceof AnalyticsSaltRepositoryInterface
    || !$query instanceof DatabaseQueryExecutorInterface
) {
    fwrite(STDERR, "ERROR: the application container is misconfigured.\n");
    exit(1);
}

$cutoff = new DateTimeImmutable("-{$days} days");

$saltsRemoved = $salts->pruneBefore($cutoff);

$rowsCleared = $query->execute(
    'UPDATE access_logs
        SET visitor_hash = NULL, referer_host = NULL, utm_source = NULL, utm_medium = NULL,
            utm_campaign = NULL, ref = NULL, client_type = NULL, is_bot = NULL
      WHERE access_date < ? AND visitor_hash IS NOT NULL',
    [$cutoff->format('Y-m-d')],
);

fwrite(STDOUT, sprintf(
    "Retention prune (>%d days, before %s): %d salts removed, %d rows cleared of visitor data.\n",
    $days,
    $cutoff->format('Y-m-d'),
    $saltsRemoved,
    $rowsCleared,
));
