<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

interface ListTextFieldsUseCaseInterface
{
    public function execute(ListTextFieldsInput $input): ListTextFieldsOutput;
}
