<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

interface GetTagByIdUseCaseInterface
{
    public function execute(GetTagByIdInput $input): GetTagByIdOutput;
}
