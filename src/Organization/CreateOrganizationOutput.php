<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

final readonly class CreateOrganizationOutput
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $plan,
        public bool $isActive,
        public ?string $customDomain,
    ) {
    }
}
