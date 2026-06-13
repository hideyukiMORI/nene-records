<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final readonly class DeleteMenuUseCase implements DeleteMenuUseCaseInterface
{
    public function __construct(
        private MenuRepositoryInterface $repository,
    ) {
    }

    public function execute(DeleteMenuInput $input): void
    {
        $existing = $this->repository->findById($input->id);

        if ($existing === null) {
            throw new MenuNotFoundException($input->id);
        }

        $this->repository->delete($input->id);
    }
}
