<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final readonly class ListMenusUseCase implements ListMenusUseCaseInterface
{
    public function __construct(
        private MenuRepositoryInterface $repository,
    ) {
    }

    public function execute(): ListMenusOutput
    {
        return new ListMenusOutput(items: $this->repository->findAll());
    }
}
