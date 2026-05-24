<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use NeNeRecords\Entity\EntityRepositoryInterface;

final readonly class DeleteEntityTypeUseCase implements DeleteEntityTypeUseCaseInterface
{
    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
        private EntityRepositoryInterface $entities,
    ) {
    }

    public function execute(DeleteEntityTypeInput $input): void
    {
        $entityType = $this->entityTypes->findById($input->id);

        if ($entityType === null) {
            throw new EntityTypeNotFoundException($input->id);
        }

        if ($this->entities->existsByEntityTypeId($input->id)) {
            throw new EntityTypeHasEntitiesException($input->id);
        }

        $this->entityTypes->delete($input->id);
    }
}
