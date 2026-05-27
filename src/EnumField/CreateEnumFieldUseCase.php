<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;

final readonly class CreateEnumFieldUseCase implements CreateEnumFieldUseCaseInterface
{
    private const ENUM_DATA_TYPE = 'enum';

    public function __construct(
        private EnumFieldRepositoryInterface $intFields,
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(CreateEnumFieldInput $input): CreateEnumFieldOutput
    {
        $entity = $this->entities->findById($input->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        $this->assertEnumFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        $id = $this->intFields->save(new EnumField(
            entityId: $input->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        ));

        return new CreateEnumFieldOutput(
            id: $id,
            entityId: $input->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        );
    }

    private function assertEnumFieldKeyRegistered(int $entityTypeId, string $fieldKey): void
    {
        $fieldDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($entityTypeId, $fieldKey);

        if ($fieldDef === null) {
            throw new FieldKeyNotRegisteredException($fieldKey, $entityTypeId);
        }

        if ($fieldDef->dataType !== self::ENUM_DATA_TYPE) {
            throw new FieldTypeMismatchException($fieldKey, self::ENUM_DATA_TYPE, $fieldDef->dataType);
        }
    }
}
