<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

final readonly class GetTextFieldByIdUseCase implements GetTextFieldByIdUseCaseInterface
{
    public function __construct(
        private TextFieldRepositoryInterface $textFields,
    ) {
    }

    public function execute(GetTextFieldByIdInput $input): GetTextFieldByIdOutput
    {
        $textField = $this->textFields->findById($input->id);

        if ($textField === null) {
            throw new TextFieldNotFoundException($input->id);
        }

        return new GetTextFieldByIdOutput(
            id: (int) $textField->id,
            entityId: $textField->entityId,
            fieldKey: $textField->fieldKey,
            value: $textField->value,
            locale: $textField->locale,
        );
    }
}
