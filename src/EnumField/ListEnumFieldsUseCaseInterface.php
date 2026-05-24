<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

interface ListEnumFieldsUseCaseInterface
{
    public function execute(ListEnumFieldsInput $input): ListEnumFieldsOutput;
}
