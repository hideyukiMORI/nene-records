<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;

final readonly class UpdateDateTimeFieldUseCase implements UpdateDateTimeFieldUseCaseInterface
{
    private const DATETIME_DATA_TYPE = 'datetime';

    public function __construct(
        private DateTimeFieldRepositoryInterface $intFields,
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(UpdateDateTimeFieldInput $input): UpdateDateTimeFieldOutput
    {
        $existing = $this->intFields->findById($input->id);

        if ($existing === null) {
            throw new DateTimeFieldNotFoundException($input->id);
        }

        $entity = $this->entities->findById($existing->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($existing->entityId);
        }

        $this->assertDateTimeFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        $updated = new DateTimeField(
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            id: $input->id,
        );
        $this->intFields->update($updated);

        return new UpdateDateTimeFieldOutput(
            id: $input->id,
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        );
    }

    private function assertDateTimeFieldKeyRegistered(int $entityTypeId, string $fieldKey): void
    {
        $fieldDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($entityTypeId, $fieldKey);

        if ($fieldDef === null) {
            throw new FieldKeyNotRegisteredException($entityTypeId, $fieldKey);
        }

        if ($fieldDef->dataType !== self::DATETIME_DATA_TYPE) {
            throw new FieldTypeMismatchException($fieldKey, self::DATETIME_DATA_TYPE, $fieldDef->dataType);
        }
    }
}
