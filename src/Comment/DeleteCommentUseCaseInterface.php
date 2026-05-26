<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

interface DeleteCommentUseCaseInterface
{
    public function execute(DeleteCommentInput $input): void;
}
