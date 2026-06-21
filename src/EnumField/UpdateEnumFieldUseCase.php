<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;

final readonly class UpdateEnumFieldUseCase implements UpdateEnumFieldUseCaseInterface
{
    private const ENUM_DATA_TYPE = 'enum';

    public function __construct(
        private EnumFieldRepositoryInterface $enumFields,
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(UpdateEnumFieldInput $input): UpdateEnumFieldOutput
    {
        $existing = $this->enumFields->findById($input->id);

        if ($existing === null) {
            throw new EnumFieldNotFoundException($input->id);
        }

        $entity = $this->entities->findById($existing->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($existing->entityId);
        }

        $this->assertEnumFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        $updated = new EnumField(
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            id: $input->id,
        );
        $this->enumFields->update($updated);

        return new UpdateEnumFieldOutput(
            id: $input->id,
            entityId: $existing->entityId,
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
