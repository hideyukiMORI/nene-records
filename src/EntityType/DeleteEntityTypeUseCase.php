<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class DeleteEntityTypeUseCase implements DeleteEntityTypeUseCaseInterface
{
    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
    ) {
    }

    public function execute(DeleteEntityTypeInput $input): void
    {
        $entityType = $this->entityTypes->findById($input->id);

        if ($entityType === null) {
            throw new EntityTypeNotFoundException($input->id);
        }

        $this->entityTypes->delete($input->id);
    }
}
