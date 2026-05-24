<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

interface CreateTagUseCaseInterface
{
    public function execute(CreateTagInput $input): CreateTagOutput;
}
