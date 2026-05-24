<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\EntityArchive\EntityArchiveRepositoryInterface;

final readonly class DeleteEntityTypeUseCase implements DeleteEntityTypeUseCaseInterface
{
    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
        private EntityRepositoryInterface $entities,
        private EntityArchiveRepositoryInterface $entityArchive,
    ) {
    }

    public function execute(DeleteEntityTypeInput $input): void
    {
        $entityType = $this->entityTypes->findById($input->id);

        if ($entityType === null) {
            throw new EntityTypeNotFoundException($input->id);
        }

        if ($this->entities->existsActiveByEntityTypeId($input->id)) {
            throw new EntityTypeHasEntitiesException($input->id);
        }

        $this->entityArchive->archiveAndPurgeSoftDeleted($entityType);
        $this->entityTypes->delete($input->id);
    }
}
