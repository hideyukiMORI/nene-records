<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;

final readonly class DetachEntityRelationUseCase implements DetachEntityRelationUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityRelationRepositoryInterface $entityRelations,
    ) {
    }

    public function execute(DetachEntityRelationInput $input): void
    {
        if ($this->entities->findById($input->entityId) === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        if (!$this->entityRelations->isAttached($input->entityId, $input->targetEntityId, $input->fieldKey)) {
            throw new RelationNotAttachedException($input->entityId, $input->targetEntityId, $input->fieldKey);
        }

        $this->entityRelations->detach($input->entityId, $input->targetEntityId, $input->fieldKey);
    }
}
