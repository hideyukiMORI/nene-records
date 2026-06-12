<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

final readonly class DeleteWidgetUseCase implements DeleteWidgetUseCaseInterface
{
    public function __construct(
        private WidgetRepositoryInterface $repository,
    ) {
    }

    public function execute(DeleteWidgetInput $input): void
    {
        if ($this->repository->findById($input->id) === null) {
            throw new WidgetNotFoundException($input->id);
        }

        $this->repository->delete($input->id);
    }
}
