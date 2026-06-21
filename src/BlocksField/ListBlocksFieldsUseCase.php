<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

final readonly class ListBlocksFieldsUseCase implements ListBlocksFieldsUseCaseInterface
{
    public function __construct(
        private BlocksFieldRepositoryInterface $blocksFields,
    ) {
    }

    public function execute(ListBlocksFieldsInput $input): ListBlocksFieldsOutput
    {
        $rows = match (true) {
            $input->entityId !== null => $this->blocksFields->findByEntityId(
                $input->entityId,
                $input->limit,
                $input->offset,
                $input->locale,
            ),
            $input->entityTypeId !== null => $this->blocksFields->findByEntityTypeId(
                $input->entityTypeId,
                $input->limit,
                $input->offset,
                $input->locale,
            ),
            default => $this->blocksFields->findAll($input->limit, $input->offset, $input->locale),
        };

        $items = array_map(
            static fn (BlocksField $blocksField) => new ListBlocksFieldItem(
                id: (int) $blocksField->id,
                entityId: $blocksField->entityId,
                fieldKey: $blocksField->fieldKey,
                value: $blocksField->value,
                locale: $blocksField->locale,
            ),
            $rows,
        );

        return new ListBlocksFieldsOutput(
            items: $items,
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
