<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface MoveEntitySubtreeUseCaseInterface
{
    public function execute(MoveEntitySubtreeInput $input): MoveEntitySubtreeOutput;
}
