<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

interface ReorderEntityTypesUseCaseInterface
{
    public function execute(ReorderEntityTypesInput $input): void;
}
