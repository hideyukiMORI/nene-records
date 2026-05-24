<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

final readonly class ListIntFieldsUseCase implements ListIntFieldsUseCaseInterface
{
    public function __construct(
        private IntFieldRepositoryInterface $intFields,
    ) {
    }

    public function execute(ListIntFieldsInput $input): ListIntFieldsOutput
    {
        $rows = $input->entityId !== null
            ? $this->intFields->findByEntityId($input->entityId, $input->limit, $input->offset)
            : $this->intFields->findAll($input->limit, $input->offset);

        $items = array_map(
            static fn (IntField $field) => new ListIntFieldItem(
                id: (int) $field->id,
                entityId: $field->entityId,
                fieldKey: $field->fieldKey,
                value: $field->value,
            ),
            $rows,
        );

        return new ListIntFieldsOutput(
            items: $items,
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
