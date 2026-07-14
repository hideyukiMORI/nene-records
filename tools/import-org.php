<?php

declare(strict_types=1);

/**
 * CLI org import for Tier A installs (#741).
 *
 * The HTTP import endpoint (POST /api/v1/superadmin/organizations/{id}/import) is
 * superadmin-only, but the Tier A installer only creates an org admin — so there is
 * no way to run the import through the web boundary on a fresh self-hosted install.
 * This CLI drains an org-export JSON straight into the target organization, using the
 * same runtime container as tools/install.php and tools/webhook-worker.php. It is an
 * operator tool: it assumes shell access to the server, exactly like the installer.
 *
 * Tenancy is preserved — every imported row is stamped with the target organization_id,
 * and the payload's own ids are remapped to fresh auto-increment values. Seeded rows
 * (default content types / setting defs) are merged, not duplicated, and the whole
 * import runs in one transaction (a failure leaves the org untouched).
 *
 * The --file may be either a JSON payload or a transport zip produced by
 * tools/export-org.php. A zip additionally carries the media originals; they are
 * placed under var/media/ at their storage_key before the DB rows are imported, so
 * an image-bearing site moves in one file (#798). Zip transport is local-disk only
 * (MEDIA_STORAGE_DRIVER=local); image derivatives regenerate on demand on import.
 *
 * Usage:
 *   php tools/import-org.php --file=export.json --org=<id|slug>
 *   php tools/import-org.php --file=export.zip  --org=<id|slug>
 *   docker compose exec -T app php tools/import-org.php --file=export.zip --org=default
 *
 * Options:
 *   --file=PATH   (required) an org-export JSON payload or transport zip (must contain "meta")
 *   --org=REF     (required) target organization: numeric id or slug
 */

use NeNeRecords\Http\RuntimeContainerFactory;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use NeNeRecords\OrgExport\OrgExportZip;
use NeNeRecords\OrgExport\OrgImportRepositoryInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @param non-empty-string $name */
function importOption(string $name): ?string
{
    foreach ($GLOBALS['argv'] as $arg) {
        if (str_starts_with((string) $arg, "--{$name}=")) {
            return substr((string) $arg, strlen($name) + 3);
        }
    }

    return null;
}

$file = importOption('file');
$org  = importOption('org');

if ($file === null || $file === '' || $org === null || $org === '') {
    fwrite(STDERR, "ERROR: both --file=PATH and --org=<id|slug> are required.\n");
    fwrite(STDERR, "Usage: php tools/import-org.php --file=export.json --org=<id|slug>\n");
    exit(1);
}

if (!is_file($file) || !is_readable($file)) {
    fwrite(STDERR, "ERROR: cannot read export file: {$file}\n");
    exit(1);
}

$projectRoot = dirname(__DIR__);

// A transport zip starts with the PK local-file / end-of-central-directory magic.
$magic = (string) file_get_contents($file, false, null, 0, 4);
$isZip = str_starts_with($magic, "PK\x03\x04") || str_starts_with($magic, "PK\x05\x06");

/** @var array<string, mixed> $payload */
$payload      = [];
$mediaPlaced  = null;
$mediaMissing = [];

if ($isZip) {
    $driver = getenv('MEDIA_STORAGE_DRIVER') ?: 'local';
    if ($driver !== 'local') {
        fwrite(STDERR, "ERROR: this archive places media originals under local disk (var/media),\n");
        fwrite(STDERR, "but MEDIA_STORAGE_DRIVER is '{$driver}'. Import the JSON-only payload and\n");
        fwrite(STDERR, "replicate the object store separately. See docs/install-tier-a.md.\n");
        exit(1);
    }

    try {
        $archive = OrgExportZip::open($file, $projectRoot . '/var/media');
    } catch (\Throwable $e) {
        fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
        exit(1);
    }

    $payload      = $archive['payload'];
    $mediaPlaced  = $archive['placed'];
    $mediaMissing = $archive['missing'];
} else {
    $raw = (string) file_get_contents($file);

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        fwrite(STDERR, "ERROR: export file is not valid JSON: {$e->getMessage()}\n");
        exit(1);
    }

    if (!is_array($decoded) || !isset($decoded['meta'])) {
        fwrite(STDERR, "ERROR: not an org-export payload (missing \"meta\" key).\n");
        exit(1);
    }

    /** @var array<string, mixed> $payload */
    $payload = $decoded;
}

$container = (new RuntimeContainerFactory($projectRoot))->create();

$organizations = $container->get(OrganizationRepositoryInterface::class);
$importer      = $container->get(OrgImportRepositoryInterface::class);

if (
    !$organizations instanceof OrganizationRepositoryInterface
    || !$importer instanceof OrgImportRepositoryInterface
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

$targetOrgId = $organization->id;

fwrite(STDOUT, sprintf("→ Importing into organization '%s' (#%d)...\n", $organization->slug, $targetOrgId));

$counts = $importer->import($targetOrgId, $payload);
$total  = array_sum($counts);

foreach ($counts as $table => $count) {
    fwrite(STDOUT, sprintf("    %-20s %d\n", $table, $count));
}

fwrite(STDOUT, sprintf("✓ Imported %d rows into '%s' (#%d).\n", $total, $organization->slug, $targetOrgId));

if ($isZip) {
    fwrite(STDOUT, sprintf("\n✓ Placed %d media original(s) under var/media/ from the archive.\n", (int) $mediaPlaced));
    if ($mediaMissing !== []) {
        fwrite(STDERR, sprintf("⚠ %d media file(s) referenced in the DB were not present in the archive:\n", count($mediaMissing)));
        foreach ($mediaMissing as $key) {
            fwrite(STDERR, "    - {$key}\n");
        }
    }
    fwrite(STDOUT, "  Image derivatives are regenerated on demand on first request.\n");
} else {
    fwrite(STDOUT, "\nNote: media DB rows were imported, but the actual files under var/media/ must be\n");
    fwrite(STDOUT, "transferred separately (rsync/FTP), or use a transport zip from tools/export-org.php.\n");
    fwrite(STDOUT, "See docs/install-tier-a.md.\n");
}

exit(0);
