<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class CreateNavigationItemUseCase implements CreateNavigationItemUseCaseInterface
{
    public function __construct(
        private NavigationItemRepositoryInterface $repository,
    ) {
    }

    public function execute(CreateNavigationItemInput $input): CreateNavigationItemOutput
    {
        $item = new NavigationItem(
            id: null,
            label: $input->label,
            url: $input->url,
            location: $input->location,
            displayOrder: $input->displayOrder,
            createdAt: '',
            updatedAt: '',
        );

        $id = $this->repository->save($item);
        $saved = $this->repository->findById($id);

        if ($saved === null) {
            throw new \RuntimeException('Failed to persist navigation item.');
        }

        return new CreateNavigationItemOutput(item: $saved);
    }
}
