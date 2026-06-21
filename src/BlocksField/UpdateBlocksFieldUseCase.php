<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;

final readonly class UpdateBlocksFieldUseCase implements UpdateBlocksFieldUseCaseInterface
{
    /** Data types whose values are stored in blocks_fields. */
    private const BLOCKS_BACKED_DATA_TYPES = ['blocks'];

    public function __construct(
        private BlocksFieldRepositoryInterface $blocksFields,
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
        private BlocksDocumentValidator $documentValidator,
    ) {
    }

    public function execute(UpdateBlocksFieldInput $input): UpdateBlocksFieldOutput
    {
        $existing = $this->blocksFields->findById($input->id);

        if ($existing === null) {
            throw new BlocksFieldNotFoundException($input->id);
        }

        $entity = $this->entities->findById($existing->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($existing->entityId);
        }

        $this->assertBlocksFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        // The trust boundary: reject malformed/unknown blocks regardless of client.
        $this->documentValidator->validate($input->value);

        $updated = new BlocksField(
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            id: $input->id,
            locale: $input->locale,
        );
        $this->blocksFields->update($updated);

        return new UpdateBlocksFieldOutput(
            id: $input->id,
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            locale: $input->locale,
        );
    }

    private function assertBlocksFieldKeyRegistered(int $entityTypeId, string $fieldKey): void
    {
        $fieldDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($entityTypeId, $fieldKey);

        if ($fieldDef === null) {
            throw new FieldKeyNotRegisteredException($fieldKey, $entityTypeId);
        }

        if (!in_array($fieldDef->dataType, self::BLOCKS_BACKED_DATA_TYPES, true)) {
            throw new FieldTypeMismatchException($fieldKey, 'blocks', $fieldDef->dataType);
        }
    }
}
