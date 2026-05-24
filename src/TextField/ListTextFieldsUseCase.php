<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

final readonly class ListTextFieldsUseCase implements ListTextFieldsUseCaseInterface
{
    public function __construct(
        private TextFieldRepositoryInterface $textFields,
    ) {
    }

    public function execute(ListTextFieldsInput $input): ListTextFieldsOutput
    {
        $rows = $this->textFields->findAll($input->limit, $input->offset);

        $items = array_map(
            static fn (TextField $textField) => new ListTextFieldItem(
                id: (int) $textField->id,
                entityId: $textField->entityId,
                fieldKey: $textField->fieldKey,
                value: $textField->value,
            ),
            $rows,
        );

        return new ListTextFieldsOutput(
            items: $items,
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
