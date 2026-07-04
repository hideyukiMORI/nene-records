<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use NeNeRecords\Setting\DefaultSettingDefsSeederInterface;

final readonly class CreateOrganizationUseCase implements CreateOrganizationUseCaseInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private DefaultContentTypeSeederInterface $contentTypeSeeder,
        private DefaultSettingDefsSeederInterface $settingDefsSeeder,
    ) {
    }

    public function execute(CreateOrganizationInput $input): CreateOrganizationOutput
    {
        $existing = $this->organizations->findBySlug($input->slug);

        if ($existing !== null) {
            throw new OrganizationSlugConflictException($input->slug);
        }

        $id = $this->organizations->save(new Organization(
            name: $input->name,
            slug: $input->slug,
            plan: $input->plan,
            isActive: $input->isActive,
            externalId: $input->externalId,
            customDomain: $input->customDomain,
        ));

        $this->contentTypeSeeder->seed($id);
        // Setting defs are org-scoped too: without this, an org created after the def
        // migrations ran has an almost empty settings surface (#711).
        $this->settingDefsSeeder->seed($id);

        return new CreateOrganizationOutput(
            id: $id,
            name: $input->name,
            slug: $input->slug,
            plan: $input->plan,
            isActive: $input->isActive,
            externalId: $input->externalId,
            customDomain: $input->customDomain,
        );
    }
}
