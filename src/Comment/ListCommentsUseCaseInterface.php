<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

interface ListCommentsUseCaseInterface
{
    public function execute(ListCommentsInput $input): ListCommentsOutput;
}
