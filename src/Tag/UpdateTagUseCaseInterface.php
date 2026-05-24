<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

interface UpdateTagUseCaseInterface
{
    public function execute(UpdateTagInput $input): UpdateTagOutput;
}
