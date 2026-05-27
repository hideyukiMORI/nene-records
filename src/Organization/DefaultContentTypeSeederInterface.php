<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

interface DefaultContentTypeSeederInterface
{
    /**
     * Seed the default entity types (Posts, Pages) and their field definitions
     * for the given organization.
     *
     * Implementations MUST be idempotent — calling this multiple times for the
     * same organization must not create duplicate rows.
     */
    public function seed(int $organizationId): void;
}
