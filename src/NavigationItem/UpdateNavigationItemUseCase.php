<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class UpdateNavigationItemUseCase implements UpdateNavigationItemUseCaseInterface
{
    public function __construct(
        private NavigationItemRepositoryInterface $repository,
    ) {
    }

    public function execute(UpdateNavigationItemInput $input): UpdateNavigationItemOutput
    {
        $existing = $this->repository->findById($input->id);

        if ($existing === null) {
            throw new NavigationItemNotFoundException($input->id);
        }

        $updated = new NavigationItem(
            id: $existing->id,
            label: $input->label,
            url: $input->url,
            location: $input->location,
            displayOrder: $input->displayOrder,
            createdAt: $existing->createdAt,
            updatedAt: '',
            menuId: $input->menuIdProvided ? $input->menuId : $existing->menuId,
        );

        $this->repository->update($updated);

        $saved = $this->repository->findById($input->id);

        if ($saved === null) {
            throw new \RuntimeException('Failed to reload navigation item after update.');
        }

        return new UpdateNavigationItemOutput(item: $saved);
    }
}
