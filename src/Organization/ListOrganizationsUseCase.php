<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

final readonly class ListOrganizationsUseCase implements ListOrganizationsUseCaseInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
    ) {
    }

    public function execute(ListOrganizationsInput $input): ListOrganizationsOutput
    {
        $organizations = $this->organizations->findAll($input->limit, $input->offset);
        $total = $this->organizations->count();

        $items = array_map(
            static fn (Organization $o): ListOrganizationItem => new ListOrganizationItem(
                id: (int) $o->id,
                name: $o->name,
                slug: $o->slug,
                plan: $o->plan,
                isActive: $o->isActive,
                customDomain: $o->customDomain,
                createdAt: $o->createdAt,
                updatedAt: $o->updatedAt,
            ),
            $organizations,
        );

        return new ListOrganizationsOutput(
            items: $items,
            total: $total,
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
