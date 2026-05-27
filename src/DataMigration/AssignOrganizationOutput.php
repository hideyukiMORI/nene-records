<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

final readonly class AssignOrganizationOutput
{
    /**
     * @param array<string, int> $tables
     */
    public function __construct(
        public int $organizationId,
        public string $organizationName,
        public int $total,
        public array $tables,
    ) {
    }
}
