<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

interface OrgImportRepositoryInterface
{
    /**
     * Imports exported data into the given organization.
     *
     * @param  array<string, mixed> $payload  The decoded JSON export payload.
     * @return array<string, int>             Row counts per table.
     */
    public function import(int $targetOrgId, array $payload): array;
}
