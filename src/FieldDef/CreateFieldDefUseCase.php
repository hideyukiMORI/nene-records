<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use NeNeRecords\EntityType\EntityTypeNotFoundException;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;

final readonly class CreateFieldDefUseCase implements CreateFieldDefUseCaseInterface
{
    public function __construct(
        private FieldDefRepositoryInterface $fieldDefs,
        private EntityTypeRepositoryInterface $entityTypes,
    ) {
    }

    public function execute(CreateFieldDefInput $input): CreateFieldDefOutput
    {
        if ($this->entityTypes->findById($input->entityTypeId) === null) {
            throw new EntityTypeNotFoundException($input->entityTypeId);
        }

        if ($input->dataType === 'relation') {
            $this->assertRelationTargetEntityTypeExists($input->targetEntityTypeId);
        }

        $existing = $this->fieldDefs->findByEntityTypeIdAndFieldKey($input->entityTypeId, $input->fieldKey);

        if ($existing !== null) {
            throw new FieldDefConflictException($input->entityTypeId, $input->fieldKey);
        }

        $id = $this->fieldDefs->save(new FieldDef(
            entityTypeId: $input->entityTypeId,
            fieldKey: $input->fieldKey,
            dataType: $input->dataType,
            targetEntityTypeId: $input->targetEntityTypeId,
            cardinality: $input->cardinality,
        ));

        return new CreateFieldDefOutput(
            id: $id,
            entityTypeId: $input->entityTypeId,
            fieldKey: $input->fieldKey,
            dataType: $input->dataType,
            targetEntityTypeId: $input->targetEntityTypeId,
            cardinality: $input->cardinality,
        );
    }

    private function assertRelationTargetEntityTypeExists(?int $targetEntityTypeId): void
    {
        if ($targetEntityTypeId === null || $this->entityTypes->findById($targetEntityTypeId) === null) {
            throw new EntityTypeNotFoundException($targetEntityTypeId ?? 0);
        }
    }
}
