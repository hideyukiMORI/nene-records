<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

/**
 * Seeds the built-in setting definitions for a newly created organization.
 *
 * Setting defs are org-scoped rows; without this seeding an org created after the
 * def migrations ran has an (almost) empty settings surface — no site name, no logo,
 * no theme persistence (#711).
 */
interface DefaultSettingDefsSeederInterface
{
    public function seed(int $organizationId): void;
}
