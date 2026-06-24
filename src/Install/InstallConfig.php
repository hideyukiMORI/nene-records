<?php

declare(strict_types=1);

namespace NeNeRecords\Install;

/** First-run install inputs (organization + initial admin). */
final readonly class InstallConfig
{
    public function __construct(
        public string $organizationName,
        public string $organizationSlug,
        public string $adminEmail,
        public string $adminPassword,
    ) {
    }
}
