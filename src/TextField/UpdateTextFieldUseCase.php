<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

use NeNeRecords\BundleField\BundleDocumentValidator;
use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;

final readonly class UpdateTextFieldUseCase implements UpdateTextFieldUseCaseInterface
{
    /** Data types whose values are stored in text_fields. */
    private const TEXT_BACKED_DATA_TYPES = ['text', 'markdown', 'html', 'bundle', 'image', 'file'];

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

        $dataType = $this->assertTextFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        if ($dataType === 'bundle') {
            (new BundleDocumentValidator())->validate($input->value);
        }

        $updated = new TextField(
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            id: $input->id,
            locale: $input->locale,
        );
        $this->textFields->update($updated);

        return new UpdateTextFieldOutput(
            id: $input->id,
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            locale: $input->locale,
        );
    }

    private function assertTextFieldKeyRegistered(int $entityTypeId, string $fieldKey): string
    {
        $fieldDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($entityTypeId, $fieldKey);

        if ($fieldDef === null) {
            throw new FieldKeyNotRegisteredException($fieldKey, $entityTypeId);
        }

        if (!in_array($fieldDef->dataType, self::TEXT_BACKED_DATA_TYPES, true)) {
            throw new FieldTypeMismatchException($fieldKey, 'text', $fieldDef->dataType);
        }

        return $fieldDef->dataType;
    }
}
