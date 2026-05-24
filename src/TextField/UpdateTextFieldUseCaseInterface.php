<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

interface UpdateTextFieldUseCaseInterface
{
    /**
     * @throws TextFieldNotFoundException when no text field matches the given id.
     */
    public function execute(UpdateTextFieldInput $input): UpdateTextFieldOutput;
}
