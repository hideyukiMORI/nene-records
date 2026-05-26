<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class ApproveCommentInput
{
    public function __construct(public int $id)
    {
    }
}
