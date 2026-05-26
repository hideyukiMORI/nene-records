<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

interface ApproveCommentUseCaseInterface
{
    public function execute(ApproveCommentInput $input): Comment;
}
