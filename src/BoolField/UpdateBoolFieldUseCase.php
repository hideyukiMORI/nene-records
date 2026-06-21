<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;

final readonly class UpdateBoolFieldUseCase implements UpdateBoolFieldUseCaseInterface
{
    private const BOOL_DATA_TYPE = 'bool';

    public function __construct(
        private BoolFieldRepositoryInterface $boolFields,
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(UpdateBoolFieldInput $input): UpdateBoolFieldOutput
    {
        $existing = $this->boolFields->findById($input->id);

        if ($existing === null) {
            throw new BoolFieldNotFoundException($input->id);
        }

        $entity = $this->entities->findById($existing->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($existing->entityId);
        }

        $this->assertBoolFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        $updated = new BoolField(
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            id: $input->id,
        );
        $this->boolFields->update($updated);

        return new UpdateBoolFieldOutput(
            id: $input->id,
            entityId: $existing->entityId,
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
