<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;

final readonly class ListEntityRelationsUseCase implements ListEntityRelationsUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
        private EntityRelationRepositoryInterface $entityRelations,
    ) {
    }

    public function execute(ListEntityRelationsInput $input): ListEntityRelationsOutput
    {
        $entity = $this->entities->findById($input->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        $fieldDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($entity->entityTypeId, $input->fieldKey);

        if ($fieldDef === null) {
            throw new FieldKeyNotRegisteredException($input->fieldKey);
        }

        if ($fieldDef->dataType !== 'relation') {
            throw new FieldTypeMismatchException($input->fieldKey, 'relation', $fieldDef->dataType);
        }

        return new ListEntityRelationsOutput(
            items: $this->entityRelations->findByEntityIdAndFieldKey($input->entityId, $input->fieldKey),
        );
    }
}
