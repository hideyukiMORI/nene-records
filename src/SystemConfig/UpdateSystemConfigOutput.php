<?php

declare(strict_types=1);

namespace NeNeRecords\SystemConfig;

final readonly class UpdateSystemConfigOutput
{
    public function __construct(
        public string $tenantResolutionMode,
        public string $tenantOrgSlug,
        public string $tenantBaseDomain,
    ) {
    }
}
