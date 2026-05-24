<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

interface AttachEntityRelationUseCaseInterface
{
    public function execute(AttachEntityRelationInput $input): AttachEntityRelationOutput;
}
