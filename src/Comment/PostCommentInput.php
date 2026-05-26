<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class PostCommentInput
{
    public function __construct(
        public int $entityId,
        public string $authorName,
        public string $authorEmail,
        public string $body,
    ) {
    }
}
