<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;

final readonly class UpdateTextFieldUseCase implements UpdateTextFieldUseCaseInterface
{
    private const TEXT_DATA_TYPE = 'text';

    public function __construct(
        private TextFieldRepositoryInterface $textFields,
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(UpdateTextFieldInput $input): UpdateTextFieldOutput
    {
        $existing = $this->textFields->findById($input->id);

        if ($existing === null) {
            throw new TextFieldNotFoundException($input->id);
        }

        $entity = $this->entities->findById($existing->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($existing->entityId);
        }

        $this->assertTextFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        $updated = new TextField(
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            id: $input->id,
        );
        $this->textFields->update($updated);

        return new UpdateTextFieldOutput(
            id: $input->id,
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        );
    }

    private function assertTextFieldKeyRegistered(int $entityTypeId, string $fieldKey): void
    {
        $fieldDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($entityTypeId, $fieldKey);

        if ($fieldDef === null) {
            throw new FieldKeyNotRegisteredException($entityTypeId, $fieldKey);
        }

        if ($fieldDef->dataType !== self::TEXT_DATA_TYPE) {
            throw new FieldTypeMismatchException($fieldKey, self::TEXT_DATA_TYPE, $fieldDef->dataType);
        }
    }
}
