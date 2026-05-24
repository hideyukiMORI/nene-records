<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

final readonly class DeleteTextFieldUseCase implements DeleteTextFieldUseCaseInterface
{
    public function __construct(
        private TextFieldRepositoryInterface $textFields,
    ) {
    }

    public function execute(DeleteTextFieldByIdInput $input): void
    {
        $textField = $this->textFields->findById($input->id);

        if ($textField === null) {
            throw new TextFieldNotFoundException($input->id);
        }

        $this->textFields->delete($input->id);
    }
}
