<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class PostCommentOutput
{
    public function __construct(
        public int $id,
        public int $entityId,
        public string $authorName,
        public string $body,
        public bool $isApproved,
        public string $createdAt,
    ) {
    }
}
