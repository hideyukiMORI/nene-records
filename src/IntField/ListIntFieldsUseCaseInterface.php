<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

interface ListIntFieldsUseCaseInterface
{
    public function execute(ListIntFieldsInput $input): ListIntFieldsOutput;
}
