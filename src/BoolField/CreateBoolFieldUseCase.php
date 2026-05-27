<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;

final readonly class CreateBoolFieldUseCase implements CreateBoolFieldUseCaseInterface
{
    private const BOOL_DATA_TYPE = 'bool';

    public function __construct(
        private BoolFieldRepositoryInterface $intFields,
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(CreateBoolFieldInput $input): CreateBoolFieldOutput
    {
        $entity = $this->entities->findById($input->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        $this->assertBoolFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        $id = $this->intFields->save(new BoolField(
            entityId: $input->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        ));

        return new CreateBoolFieldOutput(
            id: $id,
            entityId: $input->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        );
    }

    private function assertBoolFieldKeyRegistered(int $entityTypeId, string $fieldKey): void
    {
        $fieldDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($entityTypeId, $fieldKey);

        if ($fieldDef === null) {
            throw new FieldKeyNotRegisteredException($fieldKey, $entityTypeId);
        }

        if ($fieldDef->dataType !== self::BOOL_DATA_TYPE) {
            throw new FieldTypeMismatchException($fieldKey, self::BOOL_DATA_TYPE, $fieldDef->dataType);
        }
    }
}
