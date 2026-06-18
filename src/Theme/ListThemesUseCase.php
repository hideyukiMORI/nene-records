<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

final readonly class ListThemesUseCase implements ListThemesUseCaseInterface
{
    public function __construct(
        private ThemeRepositoryInterface $repository,
    ) {
    }

    public function execute(): ListThemesOutput
    {
        return new ListThemesOutput(items: $this->repository->findAll());
    }
}
