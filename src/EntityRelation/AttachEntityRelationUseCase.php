<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;

final readonly class AttachEntityRelationUseCase implements AttachEntityRelationUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
        private EntityRelationRepositoryInterface $entityRelations,
    ) {
    }

    public function execute(AttachEntityRelationInput $input): AttachEntityRelationOutput
    {
        $sourceEntity = $this->requireEntity($input->entityId);
        $fieldDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($sourceEntity->entityTypeId, $input->fieldKey);

        if ($fieldDef === null) {
            throw new FieldKeyNotRegisteredException($input->fieldKey);
        }

        if ($fieldDef->dataType !== 'relation') {
            throw new FieldTypeMismatchException($input->fieldKey, 'relation', $fieldDef->dataType);
        }

        $targetEntity = $this->requireEntity($input->targetEntityId);

        if ($fieldDef->targetEntityTypeId === null || $targetEntity->entityTypeId !== $fieldDef->targetEntityTypeId) {
            throw new RelationTargetTypeMismatchException(
                $input->fieldKey,
                $fieldDef->targetEntityTypeId ?? 0,
                $targetEntity->entityTypeId,
            );
        }

        if ($this->entityRelations->isAttached($input->entityId, $input->targetEntityId, $input->fieldKey)) {
            throw new RelationAlreadyAttachedException($input->entityId, $input->targetEntityId, $input->fieldKey);
        }

        if ($fieldDef->cardinality === 'one') {
            $this->entityRelations->detachAllForFieldKey($input->entityId, $input->fieldKey);
        }

        $this->entityRelations->attach($input->entityId, $input->targetEntityId, $input->fieldKey);

        return new AttachEntityRelationOutput(
            fieldKey: $input->fieldKey,
            targetEntityId: $input->targetEntityId,
        );
    }

    private function requireEntity(int $entityId): Entity
    {
        $entity = $this->entities->findById($entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($entityId);
        }

        return $entity;
    }
}
