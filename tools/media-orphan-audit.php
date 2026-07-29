<?php

declare(strict_types=1);

/**
 * READ-ONLY audit of orphaned media files under var/media (#1018).
 *
 * Deleting an organization purges its `media` rows but leaves the files on disk.
 * That is garbage, not an orphan in the dangerous sense: nothing can reach it and
 * it cannot surface in another tenant, so the only cost is disk. This tool answers
 * "how much is out there", which is the prerequisite for deciding whether removing
 * it is worth the risk.
 *
 * **It never deletes, moves, or writes anything.** There is deliberately no --fix
 * flag: file deletion has no undo, and the point of #1018 is to look before acting.
 *
 * What counts as an orphan: a file under the media root whose name matches no
 * `media` row. Stored names are 16 random bytes in hex, so they are unique across
 * orgs and the match is unambiguous. Derivatives live at
 * `derivatives/<preset>/<format>/<year>/<month>/<stem>.<ext>` and are re-encoded
 * (webp/avif), so they are matched on the stem rather than the full filename —
 * otherwise every derivative would look orphaned.
 *
 * Note what this CANNOT tell you: which org a leftover file belonged to. The
 * storage key has no tenant in it, and the rows that knew are gone. That
 * attribution is captured at delete time by {@see \NeNeRecords\Organization\OrgMediaInventory}.
 *
 * Usage:
 *   php tools/media-orphan-audit.php [--root=var/media] [--list] [--json]
 *   docker compose exec -T app php tools/media-orphan-audit.php --list
 *
 * Options:
 *   --root=PATH  media root; defaults to MEDIA_ROOT env, else <project>/var/media
 *   --list       print every orphan path (default: a 10-line sample)
 *   --json       machine-readable output for a cron/report pipeline
 *
 * Exit code is 0 whether or not orphans exist — this is a report, not a gate.
 */

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeNeRecords\Http\RuntimeContainerFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @param non-empty-string $name */
function auditOption(string $name): ?string
{
    foreach ($GLOBALS['argv'] as $arg) {
        if (str_starts_with($arg, "--{$name}=")) {
            return substr($arg, strlen($name) + 3);
        }
    }

    return null;
}

/** @param non-empty-string $name */
function auditFlag(string $name): bool
{
    return in_array("--{$name}", $GLOBALS['argv'], true);
}

$projectRoot = dirname(__DIR__);
$root = auditOption('root') ?? (getenv('MEDIA_ROOT') ?: $projectRoot . '/var/media');
$root = rtrim($root, '/');
$asJson = auditFlag('json');
$listAll = auditFlag('list');

if (!is_dir($root)) {
    fwrite(STDERR, "Media root not found: {$root}\n");
    exit(1);
}

$container = (new RuntimeContainerFactory($projectRoot))->create();
$query = $container->get(DatabaseQueryExecutorInterface::class);

if (!$query instanceof DatabaseQueryExecutorInterface) {
    fwrite(STDERR, "Database service unavailable.\n");
    exit(1);
}

/** @var array<string, true> $knownNames */
$knownNames = [];
/** @var array<string, true> $knownStems */
$knownStems = [];
/** @var array<int, int> $rowsByOrg */
$rowsByOrg = [];

foreach ($query->fetchAll('SELECT organization_id, stored_name FROM media', []) as $row) {
    $name = is_string($row['stored_name'] ?? null) ? $row['stored_name'] : '';

    if ($name !== '') {
        $knownNames[$name] = true;
        $knownStems[pathinfo($name, PATHINFO_FILENAME)] = true;
    }

    $org = (int) ($row['organization_id'] ?? 0);
    $rowsByOrg[$org] = ($rowsByOrg[$org] ?? 0) + 1;
}

$stats = [
    'originals' => ['total' => 0, 'orphans' => 0, 'orphan_bytes' => 0],
    'derivatives' => ['total' => 0, 'orphans' => 0, 'orphan_bytes' => 0],
];
/** @var list<array{path: string, bytes: int, modified: string, kind: string}> $orphans */
$orphans = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
);

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
    }

    $relative = ltrim(substr($file->getPathname(), strlen($root)), '/');
    $kind = str_starts_with($relative, 'derivatives/') ? 'derivatives' : 'originals';
    ++$stats[$kind]['total'];

    // Derivatives are re-encoded, so the extension differs from the original's:
    // match on the stem there, and on the full name for originals.
    $isKnown = $kind === 'derivatives'
        ? isset($knownStems[pathinfo($file->getFilename(), PATHINFO_FILENAME)])
        : isset($knownNames[$file->getFilename()]);

    if ($isKnown) {
        continue;
    }

    ++$stats[$kind]['orphans'];
    $stats[$kind]['orphan_bytes'] += $file->getSize();
    $orphans[] = [
        'path' => $relative,
        'bytes' => $file->getSize(),
        'modified' => date('Y-m-d', $file->getMTime()),
        'kind' => $kind,
    ];
}

usort($orphans, static fn (array $a, array $b): int => $b['bytes'] <=> $a['bytes']);

$totalOrphans = $stats['originals']['orphans'] + $stats['derivatives']['orphans'];
$totalBytes = $stats['originals']['orphan_bytes'] + $stats['derivatives']['orphan_bytes'];

if ($asJson) {
    echo json_encode([
        'root' => $root,
        'media_rows' => array_sum($rowsByOrg),
        'media_rows_by_org' => $rowsByOrg,
        'originals' => $stats['originals'],
        'derivatives' => $stats['derivatives'],
        'orphan_total' => $totalOrphans,
        'orphan_bytes' => $totalBytes,
        'orphans' => $listAll ? $orphans : array_slice($orphans, 0, 10),
        'orphans_truncated' => !$listAll && count($orphans) > 10,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

$mb = static fn (int $bytes): string => number_format($bytes / 1048576, 2) . ' MB';

echo "media orphan audit (READ-ONLY — nothing is deleted)\n";
echo "root: {$root}\n\n";
echo 'media rows: ' . array_sum($rowsByOrg) . "\n";
ksort($rowsByOrg);

foreach ($rowsByOrg as $org => $count) {
    echo "  org {$org}: {$count}\n";
}

printf(
    "\noriginals  : %d files, %d orphaned (%s)\n",
    $stats['originals']['total'],
    $stats['originals']['orphans'],
    $mb($stats['originals']['orphan_bytes']),
);
printf(
    "derivatives: %d files, %d orphaned (%s)\n",
    $stats['derivatives']['total'],
    $stats['derivatives']['orphans'],
    $mb($stats['derivatives']['orphan_bytes']),
);
printf("orphans    : %d files (%s)\n", $totalOrphans, $mb($totalBytes));

if ($orphans !== []) {
    $shown = $listAll ? $orphans : array_slice($orphans, 0, 10);
    echo "\n" . ($listAll ? 'all orphans' : 'largest orphans') . " (by size):\n";

    foreach ($shown as $orphan) {
        printf("  %-58s %10s  %s\n", $orphan['path'], $mb($orphan['bytes']), $orphan['modified']);
    }

    if (!$listAll && count($orphans) > 10) {
        printf("  … and %d more (use --list)\n", count($orphans) - 10);
    }
}
