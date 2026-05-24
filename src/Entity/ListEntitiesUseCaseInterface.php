<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface ListEntitiesUseCaseInterface
{
    public function execute(ListEntitiesInput $input): ListEntitiesOutput;
}
