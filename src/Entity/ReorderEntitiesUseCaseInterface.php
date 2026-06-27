<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface ReorderEntitiesUseCaseInterface
{
    public function execute(ReorderEntitiesInput $input): ReorderEntitiesOutput;
}
