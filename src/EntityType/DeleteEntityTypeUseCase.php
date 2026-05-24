<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use NeNeRecords\Entity\EntityListCriteria;
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

        $count = $this->entities->countByCriteria(new EntityListCriteria(entityTypeId: $input->id));

        if ($count > 0) {
            throw new EntityTypeHasEntitiesException($input->id, $count);
        }

        $this->entityTypes->delete($input->id);
    }
}
