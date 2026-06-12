<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class ReorderEntityTypesUseCase implements ReorderEntityTypesUseCaseInterface
{
    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
    ) {
    }

    public function execute(ReorderEntityTypesInput $input): void
    {
        $this->entityTypes->reorder($input->ids);
    }
}
