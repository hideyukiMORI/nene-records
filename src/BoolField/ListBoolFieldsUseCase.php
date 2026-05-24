<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

final readonly class ListBoolFieldsUseCase implements ListBoolFieldsUseCaseInterface
{
    public function __construct(
        private BoolFieldRepositoryInterface $intFields,
    ) {
    }

    public function execute(ListBoolFieldsInput $input): ListBoolFieldsOutput
    {
        $rows = $this->intFields->findAll($input->limit, $input->offset);

        $items = array_map(
            static fn (BoolField $field) => new ListBoolFieldItem(
                id: (int) $field->id,
                entityId: $field->entityId,
                fieldKey: $field->fieldKey,
                value: $field->value,
            ),
            $rows,
        );

        return new ListBoolFieldsOutput(
            items: $items,
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
