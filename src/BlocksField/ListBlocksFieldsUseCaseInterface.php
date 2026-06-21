<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

interface ListBlocksFieldsUseCaseInterface
{
    public function execute(ListBlocksFieldsInput $input): ListBlocksFieldsOutput;
}
