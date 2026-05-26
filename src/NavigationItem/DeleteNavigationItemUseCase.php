<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class DeleteNavigationItemUseCase implements DeleteNavigationItemUseCaseInterface
{
    public function __construct(
        private NavigationItemRepositoryInterface $repository,
    ) {
    }

    public function execute(int $id): void
    {
        if ($this->repository->findById($id) === null) {
            throw new NavigationItemNotFoundException($id);
        }

        $this->repository->delete($id);
    }
}
