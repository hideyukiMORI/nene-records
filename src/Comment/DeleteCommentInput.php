<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class DeleteCommentInput
{
    public function __construct(public int $id)
    {
    }
}
