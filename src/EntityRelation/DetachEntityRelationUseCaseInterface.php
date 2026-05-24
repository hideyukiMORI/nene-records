<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

interface DetachEntityRelationUseCaseInterface
{
    public function execute(DetachEntityRelationInput $input): void;
}
