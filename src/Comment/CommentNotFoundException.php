<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

use DomainException;

final class CommentNotFoundException extends DomainException
{
    public function __construct(int $id)
    {
        parent::__construct('Comment not found: ' . $id);
    }
}
