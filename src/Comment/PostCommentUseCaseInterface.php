<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

interface PostCommentUseCaseInterface
{
    public function execute(PostCommentInput $input): PostCommentOutput;
}
