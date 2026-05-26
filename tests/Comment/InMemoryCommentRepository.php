<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Comment;

use NeNeRecords\Comment\Comment;
use NeNeRecords\Comment\CommentNotFoundException;
use NeNeRecords\Comment\CommentRepositoryInterface;

final class InMemoryCommentRepository implements CommentRepositoryInterface
{
    /** @var array<int, Comment> */
    private array $comments = [];

    private int $nextId = 1;

    /** @return Comment[] */
    public function listByEntity(int $entityId, bool $approvedOnly): array
    {
        $items = array_filter(
            $this->comments,
            static fn (Comment $c): bool =>
                $c->entityId === $entityId && (!$approvedOnly || $c->isApproved),
        );

        $items = array_values($items);
        usort($items, static fn (Comment $a, Comment $b): int => $a->createdAt <=> $b->createdAt);

        return $items;
    }

    /** @return Comment[] */
    public function listAll(): array
    {
        $items = array_values($this->comments);
        usort($items, static fn (Comment $a, Comment $b): int => $b->createdAt <=> $a->createdAt);

        return $items;
    }

    public function findById(int $id): Comment
    {
        if (!isset($this->comments[$id])) {
            throw new CommentNotFoundException($id);
        }

        return $this->comments[$id];
    }

    public function create(int $entityId, string $authorName, string $authorEmail, string $body): Comment
    {
        $id = $this->nextId++;
        $comment = new Comment(
            id: $id,
            entityId: $entityId,
            authorName: $authorName,
            authorEmail: $authorEmail,
            body: $body,
            isApproved: false,
            createdAt: date('Y-m-d H:i:s'),
        );
        $this->comments[$id] = $comment;

        return $comment;
    }

    public function approve(int $id): Comment
    {
        if (!isset($this->comments[$id])) {
            throw new CommentNotFoundException($id);
        }

        $old = $this->comments[$id];
        $approved = new Comment(
            id: $old->id,
            entityId: $old->entityId,
            authorName: $old->authorName,
            authorEmail: $old->authorEmail,
            body: $old->body,
            isApproved: true,
            createdAt: $old->createdAt,
        );
        $this->comments[$id] = $approved;

        return $approved;
    }

    public function delete(int $id): void
    {
        if (!isset($this->comments[$id])) {
            throw new CommentNotFoundException($id);
        }

        unset($this->comments[$id]);
    }
}
