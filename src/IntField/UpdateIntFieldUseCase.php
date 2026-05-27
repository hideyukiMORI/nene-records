<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;

final readonly class UpdateIntFieldUseCase implements UpdateIntFieldUseCaseInterface
{
    private const INT_DATA_TYPE = 'int';

    public function __construct(
        private IntFieldRepositoryInterface $intFields,
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(UpdateIntFieldInput $input): UpdateIntFieldOutput
    {
        $existing = $this->intFields->findById($input->id);

        if ($existing === null) {
            throw new IntFieldNotFoundException($input->id);
        }

        $entity = $this->entities->findById($existing->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($existing->entityId);
        }

        $this->assertIntFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        $updated = new IntField(
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            id: $input->id,
        );
        $this->intFields->update($updated);

        return new UpdateIntFieldOutput(
            id: $input->id,
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        );
    }

    private function assertIntFieldKeyRegistered(int $entityTypeId, string $fieldKey): void
    {
        $fieldDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($entityTypeId, $fieldKey);

        if ($fieldDef === null) {
            throw new FieldKeyNotRegisteredException($fieldKey, $entityTypeId);
        }

        if ($fieldDef->dataType !== self::INT_DATA_TYPE) {
            throw new FieldTypeMismatchException($fieldKey, self::INT_DATA_TYPE, $fieldDef->dataType);
        }
    }
}
