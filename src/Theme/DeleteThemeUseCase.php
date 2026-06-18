<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

final readonly class DeleteThemeUseCase implements DeleteThemeUseCaseInterface
{
    public function __construct(
        private ThemeRepositoryInterface $repository,
    ) {
    }

    public function execute(DeleteThemeInput $input): void
    {
        $existing = $this->repository->findByKey($input->themeKey);

        if ($existing === null) {
            throw new ThemeNotFoundException($input->themeKey);
        }

        $this->repository->deleteByKey($input->themeKey);
    }
}
