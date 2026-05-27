<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

final readonly class GetDataMigrationStatusOutput
{
    /**
     * @param array<string, int> $tables
     */
    public function __construct(
        public int $total,
        public array $tables,
    ) {
    }
}
