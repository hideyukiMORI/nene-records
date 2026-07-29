<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeNeRecords\Media\StorageInterface;

final readonly class PdoOrgMediaInventoryReader implements OrgMediaInventoryReaderInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private StorageInterface $storage,
    ) {
    }

    public function forOrganization(int $organizationId): OrgMediaInventory
    {
        $rows = $this->query->fetchAll(
            'SELECT storage_key, stored_name FROM media WHERE organization_id = ? ORDER BY id',
            [$organizationId],
        );

        $entries = [];

        foreach ($rows as $row) {
            $key = $this->keyOf($row);

            if ($key === '') {
                continue;
            }

            // `exists()` before `size()`: LocalStorage::size() returns 0 for a missing
            // file, so size alone cannot distinguish "gone" from "empty".
            $present = $this->storage->exists($key);

            $entries[] = [
                'key' => $key,
                'bytes' => $present ? $this->storage->size($key) : 0,
                'present' => $present,
            ];
        }

        return OrgMediaInventory::fromEntries($organizationId, $entries);
    }

    /**
     * `storage_key` is the real path and the column that matters. It defaults to ''
     * in the schema, so rows written before it existed fall back to `stored_name`
     * — which is the bare filename, not a path. Such a row cannot be resolved to a
     * file, and reporting it as missing is the honest outcome.
     *
     * @param array<string, mixed> $row
     */
    private function keyOf(array $row): string
    {
        $storageKey = isset($row['storage_key']) && is_string($row['storage_key']) ? trim($row['storage_key']) : '';

        if ($storageKey !== '') {
            return $storageKey;
        }

        return isset($row['stored_name']) && is_string($row['stored_name']) ? trim($row['stored_name']) : '';
    }
}
