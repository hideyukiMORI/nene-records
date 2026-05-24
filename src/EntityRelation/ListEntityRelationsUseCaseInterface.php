<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

interface ListEntityRelationsUseCaseInterface
{
    public function execute(ListEntityRelationsInput $input): ListEntityRelationsOutput;
}
