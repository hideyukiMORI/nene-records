<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class ListCommentsItem
{
    public function __construct(
        public int $id,
        public int $entityId,
        public string $authorName,
        public string $authorEmail,
        public string $body,
        public bool $isApproved,
        public string $createdAt,
    ) {
    }

    public static function fromComment(Comment $comment): self
    {
        return new self(
            id: $comment->id,
            entityId: $comment->entityId,
            authorName: $comment->authorName,
            authorEmail: $comment->authorEmail,
            body: $comment->body,
            isApproved: $comment->isApproved,
            createdAt: $comment->createdAt,
        );
    }
}
