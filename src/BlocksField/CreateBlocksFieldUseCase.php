<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;

final readonly class CreateBlocksFieldUseCase implements CreateBlocksFieldUseCaseInterface
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

    public function execute(CreateBlocksFieldInput $input): CreateBlocksFieldOutput
    {
        $entity = $this->entities->findById($input->entityId);

        if ($entity === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        $this->assertBlocksFieldKeyRegistered($entity->entityTypeId, $input->fieldKey);

        // The trust boundary: reject malformed/unknown blocks regardless of client.
        $this->documentValidator->validate($input->value);

        $id = $this->blocksFields->save(new BlocksField(
            entityId: $input->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            locale: $input->locale,
        ));

        return new CreateBlocksFieldOutput(
            id: $id,
            entityId: $input->entityId,
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
