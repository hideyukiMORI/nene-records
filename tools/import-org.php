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
 * Usage:
 *   php tools/import-org.php --file=export.json --org=<id|slug>
 *   docker compose exec -T app php tools/import-org.php --file=export.json --org=default
 *
 * Options:
 *   --file=PATH   (required) path to an org-export JSON payload (must contain "meta")
 *   --org=REF     (required) target organization: numeric id or slug
 */

use NeNeRecords\Http\RuntimeContainerFactory;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
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

$container = (new RuntimeContainerFactory(dirname(__DIR__)))->create();

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
fwrite(STDOUT, "\nNote: media DB rows were imported, but the actual files under var/media/ must be\n");
fwrite(STDOUT, "transferred separately (rsync/FTP). See docs/install-tier-a.md.\n");

exit(0);
