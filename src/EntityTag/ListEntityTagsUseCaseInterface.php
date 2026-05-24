<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

interface ListEntityTagsUseCaseInterface
{
    public function execute(ListEntityTagsInput $input): ListEntityTagsOutput;
}
