<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

final readonly class CreateOrganizationUseCase implements CreateOrganizationUseCaseInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
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
