<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;

final readonly class CreateIntFieldUseCase implements CreateIntFieldUseCaseInterface
{
    private const INT_DATA_TYPE = 'int';

    public function __construct(
        private IntFieldRepositoryInterface $intFields,
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(CreateIntFieldInput $input): CreateIntFieldOutput
    {
        $entity = $this->entities->findById($input->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        $this->assertIntFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        $id = $this->intFields->save(new IntField(
            entityId: $input->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        ));

        return new CreateIntFieldOutput(
            id: $id,
            entityId: $input->entityId,
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
