<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use NeNeRecords\EntityType\EntityTypeNotFoundException;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;

final readonly class UpdateFieldDefUseCase implements UpdateFieldDefUseCaseInterface
{
    public function __construct(
        private FieldDefRepositoryInterface $fieldDefs,
        private EntityTypeRepositoryInterface $entityTypes,
    ) {
    }

    public function execute(UpdateFieldDefInput $input): UpdateFieldDefOutput
    {
        $fieldDef = $this->fieldDefs->findById($input->id);

        if ($fieldDef === null) {
            throw new FieldDefNotFoundException($input->id);
        }

        if ($this->entityTypes->findById($input->entityTypeId) === null) {
            throw new EntityTypeNotFoundException($input->entityTypeId);
        }

        if ($input->dataType === 'relation') {
            $targetEntityTypeId = $input->targetEntityTypeId;

            if ($targetEntityTypeId === null || $this->entityTypes->findById($targetEntityTypeId) === null) {
                throw new EntityTypeNotFoundException($targetEntityTypeId ?? 0);
            }
        }

        $existing = $this->fieldDefs->findByEntityTypeIdAndFieldKey($input->entityTypeId, $input->fieldKey);

        if ($existing !== null && $existing->id !== $input->id) {
            throw new FieldDefConflictException($input->entityTypeId, $input->fieldKey);
        }

        $updated = new FieldDef(
            entityTypeId: $input->entityTypeId,
            fieldKey: $input->fieldKey,
            dataType: $input->dataType,
            id: $input->id,
            targetEntityTypeId: $input->targetEntityTypeId,
            cardinality: $input->cardinality,
        );
        $this->fieldDefs->update($updated);

        return new UpdateFieldDefOutput(
            id: $input->id,
            entityTypeId: $input->entityTypeId,
            fieldKey: $input->fieldKey,
            dataType: $input->dataType,
            targetEntityTypeId: $input->targetEntityTypeId,
            cardinality: $input->cardinality,
        );
    }
}
