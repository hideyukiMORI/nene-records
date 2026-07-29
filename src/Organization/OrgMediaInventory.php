<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

/**
 * What an organization's media occupies on disk, captured *before* the org is
 * deleted (#1018).
 *
 * Deleting an org purges the `media` rows (see {@see OrganizationScopedSchema})
 * but leaves the files under `var/media`. That is garbage rather than an orphan —
 * nothing can reach it, so it cannot leak into another tenant — and the only cost
 * is disk. Deleting the files during the purge was rejected deliberately: file
 * removal happens outside the DB transaction, so a rollback would restore the rows
 * while the bytes stayed gone, and file deletion has no undo.
 *
 * So the first step is to write down what was left behind. The timing matters:
 * **once the rows are purged, nothing on disk records which org a file belonged
 * to** — the storage key is `YYYY/MM/<random>.<ext>`, with no tenant in the path.
 * This snapshot is the only moment that attribution exists.
 *
 * Derivatives are not listed here. They are not in the DB at all (they are
 * generated on demand under `derivatives/<preset>/<format>/…` from the original's
 * stem), so they cannot be enumerated from org-scoped rows. `tools/media-orphan-audit.php`
 * scans the filesystem and reports both.
 *
 * @phpstan-type InventoryEntry array{key: string, bytes: int, present: bool}
 */
final readonly class OrgMediaInventory
{
    /** @param list<InventoryEntry> $entries */
    private function __construct(
        public int $organizationId,
        public int $fileCount,
        public int $totalBytes,
        public int $missingCount,
        public array $entries,
    ) {
    }

    /** @param list<InventoryEntry> $entries */
    public static function fromEntries(int $organizationId, array $entries): self
    {
        $present = array_values(array_filter($entries, static fn (array $e): bool => $e['present']));

        return new self(
            organizationId: $organizationId,
            fileCount: count($present),
            totalBytes: array_sum(array_map(static fn (array $e): int => $e['bytes'], $present)),
            missingCount: count($entries) - count($present),
            entries: $entries,
        );
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    /**
     * Log-friendly context. Keys are capped so a tenant with thousands of files
     * cannot produce an unbounded log line; the counts stay exact either way, and
     * the audit tool can always re-derive the full list from disk.
     *
     * @return array{organization_id: int, file_count: int, total_bytes: int, missing_count: int, keys: list<string>, keys_truncated: bool}
     */
    public function toLogContext(int $maxKeys = 200): array
    {
        $keys = array_map(static fn (array $e): string => $e['key'], $this->entries);

        return [
            'organization_id' => $this->organizationId,
            'file_count' => $this->fileCount,
            'total_bytes' => $this->totalBytes,
            // Rows whose file was already gone. Not a problem to fix here, but a
            // number that should be 0; anything else means the two sides drifted.
            'missing_count' => $this->missingCount,
            'keys' => array_slice($keys, 0, $maxKeys),
            'keys_truncated' => count($keys) > $maxKeys,
        ];
    }
}
