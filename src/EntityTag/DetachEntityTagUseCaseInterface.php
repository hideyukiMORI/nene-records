<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

interface DetachEntityTagUseCaseInterface
{
    public function execute(DetachEntityTagInput $input): void;
}
