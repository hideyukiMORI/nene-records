<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

final readonly class ListEnumFieldsUseCase implements ListEnumFieldsUseCaseInterface
{
    public function __construct(
        private EnumFieldRepositoryInterface $intFields,
    ) {
    }

    public function execute(ListEnumFieldsInput $input): ListEnumFieldsOutput
    {
        $rows = $input->entityId !== null
            ? $this->intFields->findByEntityId($input->entityId, $input->limit, $input->offset)
            : $this->intFields->findAll($input->limit, $input->offset);

        $items = array_map(
            static fn (EnumField $field) => new ListEnumFieldItem(
                id: (int) $field->id,
                entityId: $field->entityId,
                fieldKey: $field->fieldKey,
                value: $field->value,
            ),
            $rows,
        );

        return new ListEnumFieldsOutput(
            items: $items,
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
