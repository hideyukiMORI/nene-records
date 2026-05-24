<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

interface TagRepositoryInterface
{
    public function findById(int $id): ?Tag;

    public function findBySlug(string $slug): ?Tag;

    /** @return list<Tag> */
    public function findAll(int $limit, int $offset): array;

    public function save(Tag $tag): int;

    public function update(Tag $tag): void;

    public function delete(int $id): void;
}
