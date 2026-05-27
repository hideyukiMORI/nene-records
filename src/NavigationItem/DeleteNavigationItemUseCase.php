<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class DeleteNavigationItemUseCase implements DeleteNavigationItemUseCaseInterface
{
    public function __construct(
        private NavigationItemRepositoryInterface $repository,
    ) {
    }

    public function execute(DeleteNavigationItemInput $input): void
    {
        if ($this->repository->findById($input->id) === null) {
            throw new NavigationItemNotFoundException($input->id);
        }

        $this->repository->delete($input->id);
    }
}
