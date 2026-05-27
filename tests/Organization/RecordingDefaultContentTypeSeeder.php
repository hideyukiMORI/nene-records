<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

use NeNeRecords\Organization\DefaultContentTypeSeederInterface;

/**
 * Test double: records every `seed()` call so assertions can verify that
 * CreateOrganizationUseCase actually invokes the seeder with the new org ID.
 */
final class RecordingDefaultContentTypeSeeder implements DefaultContentTypeSeederInterface
{
    /** @var list<int> */
    public array $seededOrgIds = [];

    public function seed(int $organizationId): void
    {
        $this->seededOrgIds[] = $organizationId;
    }
}
