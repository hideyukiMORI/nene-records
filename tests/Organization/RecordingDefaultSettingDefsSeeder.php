<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

use NeNeRecords\Setting\DefaultSettingDefsSeederInterface;

/**
 * Test double: records which organization ids the setting-defs seeder was asked to
 * seed, so use-case tests can assert org creation triggers it (#711).
 */
final class RecordingDefaultSettingDefsSeeder implements DefaultSettingDefsSeederInterface
{
    /** @var list<int> */
    public array $seededOrgIds = [];

    public function seed(int $organizationId): void
    {
        $this->seededOrgIds[] = $organizationId;
    }
}
