<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

interface DataMigrationRepositoryInterface
{
    /**
     * Returns the count of records with organization_id = 0 per table.
     *
     * @return array<string, int>
     */
    public function countUnassigned(): array;

    /**
     * Reassigns all records with organization_id = 0 to the given org.
     * Returns the number of updated rows per table.
     *
     * @return array<string, int>
     */
    public function assignAll(int $targetOrgId): array;
}
