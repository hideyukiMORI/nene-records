<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class ListCommentsInput
{
    public function __construct(
        public int $entityId,
        public bool $approvedOnly,
    ) {
    }
}
