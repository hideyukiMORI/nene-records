<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

interface CommentRepositoryInterface
{
    /** @return Comment[] */
    public function listByEntity(int $entityId, bool $approvedOnly): array;

    /** @return Comment[] */
    public function listAll(): array;

    public function findById(int $id): Comment;

    public function create(int $entityId, string $authorName, string $authorEmail, string $body): Comment;

    public function approve(int $id): Comment;

    public function delete(int $id): void;
}
