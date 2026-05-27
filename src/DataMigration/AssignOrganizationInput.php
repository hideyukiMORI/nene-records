<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

final readonly class AssignOrganizationInput
{
    public function __construct(
        public int $targetOrgId,
    ) {
    }
}
