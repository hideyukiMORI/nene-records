<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

interface GetTextFieldByIdUseCaseInterface
{
    /**
     * @throws TextFieldNotFoundException when no text field matches the given id.
     */
    public function execute(GetTextFieldByIdInput $input): GetTextFieldByIdOutput;
}
