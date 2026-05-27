<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

final readonly class UpdateOrganizationInput
{
    /**
     * @param ?string $name              null = keep current
     * @param ?string $slug              null = keep current
     * @param ?string $plan              null = keep current
     * @param ?bool   $isActive          null = keep current
     * @param bool    $updateExternalId  true = apply $externalId (even when null to clear)
     * @param ?string $externalId        value to set when $updateExternalId is true; null clears the field
     * @param bool    $updateCustomDomain  true = apply $customDomain (even when null to clear)
     * @param ?string $customDomain      value to set when $updateCustomDomain is true; null clears the field
     */
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $slug,
        public ?string $plan,
        public ?bool $isActive,
        public bool $updateCustomDomain,
        public ?string $customDomain,
        public bool $updateExternalId = false,
        public ?string $externalId = null,
    ) {
    }
}
