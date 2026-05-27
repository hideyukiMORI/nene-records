<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\DataMigration;

use NeNeRecords\DataMigration\DataMigrationRepositoryInterface;

final class InMemoryDataMigrationRepository implements DataMigrationRepositoryInterface
{
    /** @var array<string, int> */
    public array $unassigned;

    public int $targetOrgId = 0;

    /** @param array<string, int> $unassigned */
    public function __construct(array $unassigned = [])
    {
        $this->unassigned = $unassigned;
    }

    /** @return array<string, int> */
    public function countUnassigned(): array
    {
        return $this->unassigned;
    }

    /** @return array<string, int> */
    public function assignAll(int $targetOrgId): array
    {
        $this->targetOrgId = $targetOrgId;
        $migrated = $this->unassigned;
        foreach (array_keys($this->unassigned) as $table) {
            $this->unassigned[$table] = 0;
        }

        return $migrated;
    }
}
