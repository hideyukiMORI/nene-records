<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

interface ListDateTimeFieldsUseCaseInterface
{
    public function execute(ListDateTimeFieldsInput $input): ListDateTimeFieldsOutput;
}
