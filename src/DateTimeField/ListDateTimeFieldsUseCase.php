<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

final readonly class ListDateTimeFieldsUseCase implements ListDateTimeFieldsUseCaseInterface
{
    public function __construct(
        private DateTimeFieldRepositoryInterface $intFields,
    ) {
    }

    public function execute(ListDateTimeFieldsInput $input): ListDateTimeFieldsOutput
    {
        $rows = $input->entityId !== null
            ? $this->intFields->findByEntityId($input->entityId, $input->limit, $input->offset)
            : $this->intFields->findAll($input->limit, $input->offset);

        $items = array_map(
            static fn (DateTimeField $field) => new ListDateTimeFieldItem(
                id: (int) $field->id,
                entityId: $field->entityId,
                fieldKey: $field->fieldKey,
                value: $field->value,
            ),
            $rows,
        );

        return new ListDateTimeFieldsOutput(
            items: $items,
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
