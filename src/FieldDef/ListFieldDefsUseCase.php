<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

final readonly class ListFieldDefsUseCase implements ListFieldDefsUseCaseInterface
{
    public function __construct(
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(ListFieldDefsInput $input): ListFieldDefsOutput
    {
        $rows = $this->fieldDefs->findAll($input->entityTypeId, $input->limit, $input->offset);

        $items = array_map(
            static fn (FieldDef $fieldDef) => new ListFieldDefItem(
                id: (int) $fieldDef->id,
                entityTypeId: $fieldDef->entityTypeId,
                fieldKey: $fieldDef->fieldKey,
                dataType: $fieldDef->dataType,
            ),
            $rows,
        );

        return new ListFieldDefsOutput(
            items: $items,
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
