<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

final readonly class UpdateTextFieldUseCase implements UpdateTextFieldUseCaseInterface
{
    public function __construct(
        private TextFieldRepositoryInterface $textFields,
    ) {
    }

    public function execute(UpdateTextFieldInput $input): UpdateTextFieldOutput
    {
        $existing = $this->textFields->findById($input->id);

        if ($existing === null) {
            throw new TextFieldNotFoundException($input->id);
        }

        $updated = new TextField(
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
            id: $input->id,
        );
        $this->textFields->update($updated);

        return new UpdateTextFieldOutput(
            id: $input->id,
            entityId: $existing->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        );
    }
}
