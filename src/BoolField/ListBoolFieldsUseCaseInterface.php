<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

interface ListBoolFieldsUseCaseInterface
{
    public function execute(ListBoolFieldsInput $input): ListBoolFieldsOutput;
}
