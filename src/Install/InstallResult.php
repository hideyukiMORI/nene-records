<?php

declare(strict_types=1);

namespace NeNeRecords\Install;

/** Outcome of a first-run install (idempotent — `*Created` is false when already present). */
final readonly class InstallResult
{
    public function __construct(
        public int $organizationId,
        public string $organizationSlug,
        public bool $organizationCreated,
        public string $adminEmail,
        public bool $adminCreated,
    ) {
    }
}
