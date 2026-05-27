<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

final readonly class CreateOrganizationInput
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $plan = 'free',
        public bool $isActive = true,
        public ?string $customDomain = null,
    ) {
    }
}
