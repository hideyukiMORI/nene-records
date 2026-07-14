<?php

declare(strict_types=1);

/**
 * CLI org export for Tier A transport (#741 / #798).
 *
 * Produces a single self-contained zip — the DB payload (export.json) plus every
 * media original at its storage_key path — so a whole org, images included, moves
 * in one file. The counterpart tools/import-org.php extracts it on the target.
 *
 * The HTTP export endpoint (GET /api/v1/superadmin/organizations/{id}/export)
 * returns DB rows only; media originals live on the server filesystem, so bundling
 * them is an operator task with shell access, exactly like tools/install.php.
 *
 * Local-disk storage only: with MEDIA_STORAGE_DRIVER=s3 the originals live in the
 * bucket (not under var/media), so this tool refuses to run — replicate the bucket
 * to the target instead, or use the JSON-only HTTP export. Image derivatives are
 * regenerated on demand on the target, so only originals are bundled.
 *
 * Usage:
 *   php tools/export-org.php --org=<id|slug> --out=export.zip
 *   docker compose exec -T app php tools/export-org.php --org=aozora --out=/tmp/aozora.zip
 *
 * Options:
 *   --org=REF     (required) source organization: numeric id or slug
 *   --out=PATH    (required) destination zip path
 */

use NeNeRecords\Http\RuntimeContainerFactory;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use NeNeRecords\OrgExport\OrgExportPayloadBuilder;
use NeNeRecords\OrgExport\OrgExportZip;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @param non-empty-string $name */
function exportOption(string $name): ?string
{
    foreach ($GLOBALS['argv'] as $arg) {
        if (str_starts_with((string) $arg, "--{$name}=")) {
            return substr((string) $arg, strlen($name) + 3);
        }
    }

    return null;
}

$org = exportOption('org');
$out = exportOption('out');

if ($org === null || $org === '' || $out === null || $out === '') {
    fwrite(STDERR, "ERROR: both --org=<id|slug> and --out=PATH are required.\n");
    fwrite(STDERR, "Usage: php tools/export-org.php --org=<id|slug> --out=export.zip\n");
    exit(1);
}

$driver = getenv('MEDIA_STORAGE_DRIVER') ?: 'local';
if ($driver !== 'local') {
    fwrite(STDERR, "ERROR: the zip export bundles media originals from local disk (var/media),\n");
    fwrite(STDERR, "but MEDIA_STORAGE_DRIVER is '{$driver}'. Replicate the object store separately,\n");
    fwrite(STDERR, "or use the JSON-only HTTP export endpoint. See docs/install-tier-a.md.\n");
    exit(1);
}

$projectRoot = dirname(__DIR__);
$mediaRoot   = $projectRoot . '/var/media';

$container = (new RuntimeContainerFactory($projectRoot))->create();

$organizations = $container->get(OrganizationRepositoryInterface::class);
$builder       = $container->get(OrgExportPayloadBuilder::class);

if (
    !$organizations instanceof OrganizationRepositoryInterface
    || !$builder instanceof OrgExportPayloadBuilder
) {
    fwrite(STDERR, "ERROR: the application container is misconfigured.\n");
    exit(1);
}

$organization = ctype_digit($org)
    ? $organizations->findById((int) $org)
    : $organizations->findBySlug($org);

if ($organization === null || $organization->id === null) {
    fwrite(STDERR, "ERROR: no organization found for '{$org}'.\n");
    exit(1);
}

$sourceOrgId = $organization->id;

fwrite(STDOUT, sprintf("→ Exporting organization '%s' (#%d)...\n", $organization->slug, $sourceOrgId));

$payload    = $builder->build($sourceOrgId);
$mediaRows  = is_array($payload['media'] ?? null) ? count((array) $payload['media']) : 0;

try {
    $result = OrgExportZip::create($out, $payload, $mediaRoot);
} catch (\Throwable $e) {
    fwrite(STDERR, 'ERROR: failed to build the archive: ' . $e->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, sprintf("    media rows            %d\n", $mediaRows));
fwrite(STDOUT, sprintf("    media files bundled   %d\n", $result['added']));

if ($result['missing'] !== []) {
    fwrite(STDERR, sprintf("⚠ %d media original(s) referenced in the DB were not found on disk:\n", count($result['missing'])));
    foreach ($result['missing'] as $key) {
        fwrite(STDERR, "    - {$key}\n");
    }
}

fwrite(STDOUT, sprintf("✓ Wrote %s (%d bytes).\n", $out, (int) @filesize($out)));
fwrite(STDOUT, sprintf("  Import on the target with:\n    php tools/import-org.php --file=%s --org=<id|slug>\n", basename($out)));

exit(0);
