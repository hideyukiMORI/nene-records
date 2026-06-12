<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;

final readonly class CreateTextFieldUseCase implements CreateTextFieldUseCaseInterface
{
    /** Data types whose values are stored in text_fields. */
    private const TEXT_BACKED_DATA_TYPES = ['text', 'markdown', 'html', 'image', 'file'];

    public function __construct(
        private TextFieldRepositoryInterface $textFields,
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(CreateTextFieldInput $input): CreateTextFieldOutput
    {
        $entity = $this->entities->findById($input->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        $this->assertTextFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        $id = $this->textFields->save(new TextField(
            entityId: $input->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            locale: $input->locale,
        ));

        return new CreateTextFieldOutput(
            id: $id,
            entityId: $input->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            locale: $input->locale,
        );
    }

    private function assertTextFieldKeyRegistered(int $entityTypeId, string $fieldKey): void
    {
        $fieldDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($entityTypeId, $fieldKey);

        if ($fieldDef === null) {
            throw new FieldKeyNotRegisteredException($fieldKey, $entityTypeId);
        }

        if (!in_array($fieldDef->dataType, self::TEXT_BACKED_DATA_TYPES, true)) {
            throw new FieldTypeMismatchException($fieldKey, 'text', $fieldDef->dataType);
        }
    }
}
