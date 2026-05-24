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

        $existing = $this->fieldDefs->findByEntityTypeIdAndFieldKey($input->entityTypeId, $input->fieldKey);

        if ($existing !== null) {
            throw new FieldDefConflictException($input->entityTypeId, $input->fieldKey);
        }

        $id = $this->fieldDefs->save(new FieldDef(
            entityTypeId: $input->entityTypeId,
            fieldKey: $input->fieldKey,
            dataType: $input->dataType,
        ));

        return new CreateFieldDefOutput(
            id: $id,
            entityTypeId: $input->entityTypeId,
            fieldKey: $input->fieldKey,
            dataType: $input->dataType,
        );
    }
}
