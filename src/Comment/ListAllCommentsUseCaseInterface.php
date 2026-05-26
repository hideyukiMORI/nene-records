<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

interface ListAllCommentsUseCaseInterface
{
    public function execute(): ListCommentsOutput;
}
