<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

interface DeleteTextFieldUseCaseInterface
{
    /**
     * Soft-deletes the text field.
     *
     * @throws TextFieldNotFoundException when no text field matches the given id.
     */
    public function execute(DeleteTextFieldByIdInput $input): void;
}
