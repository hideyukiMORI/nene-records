<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

interface AttachEntityTagUseCaseInterface
{
    public function execute(AttachEntityTagInput $input): AttachEntityTagOutput;
}
