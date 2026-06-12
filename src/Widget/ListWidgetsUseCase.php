<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

final readonly class ListWidgetsUseCase implements ListWidgetsUseCaseInterface
{
    public function __construct(
        private WidgetRepositoryInterface $repository,
    ) {
    }

    public function execute(): ListWidgetsOutput
    {
        return new ListWidgetsOutput(items: $this->repository->findAll());
    }
}
