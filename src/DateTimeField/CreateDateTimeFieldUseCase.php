<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;

final readonly class CreateDateTimeFieldUseCase implements CreateDateTimeFieldUseCaseInterface
{
    private const DATETIME_DATA_TYPE = 'datetime';

    public function __construct(
        private DateTimeFieldRepositoryInterface $intFields,
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(CreateDateTimeFieldInput $input): CreateDateTimeFieldOutput
    {
        $entity = $this->entities->findById($input->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        $this->assertDateTimeFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        $id = $this->intFields->save(new DateTimeField(
            entityId: $input->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        ));

        return new CreateDateTimeFieldOutput(
            id: $id,
            entityId: $input->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        );
    }

    private function assertDateTimeFieldKeyRegistered(int $entityTypeId, string $fieldKey): void
    {
        $fieldDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($entityTypeId, $fieldKey);

        if ($fieldDef === null) {
            throw new FieldKeyNotRegisteredException($fieldKey, $entityTypeId);
        }

        if ($fieldDef->dataType !== self::DATETIME_DATA_TYPE) {
            throw new FieldTypeMismatchException($fieldKey, self::DATETIME_DATA_TYPE, $fieldDef->dataType);
        }
    }
}
