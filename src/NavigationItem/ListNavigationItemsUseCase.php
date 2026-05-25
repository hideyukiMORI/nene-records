<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class ListNavigationItemsUseCase implements ListNavigationItemsUseCaseInterface
{
    public function __construct(
        private NavigationItemRepositoryInterface $repository,
    ) {
    }

    public function execute(): ListNavigationItemsOutput
    {
        return new ListNavigationItemsOutput(items: $this->repository->findAll());
    }
}
