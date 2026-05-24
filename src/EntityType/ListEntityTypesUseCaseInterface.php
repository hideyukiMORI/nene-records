<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

interface ListEntityTypesUseCaseInterface
{
    public function execute(ListEntityTypesInput $input): ListEntityTypesOutput;
}
