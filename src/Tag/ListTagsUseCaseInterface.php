<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

interface ListTagsUseCaseInterface
{
    public function execute(ListTagsInput $input): ListTagsOutput;
}
