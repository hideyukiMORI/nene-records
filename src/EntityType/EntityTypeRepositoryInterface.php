<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

interface EntityTypeRepositoryInterface
{
    public function findById(int $id): ?EntityType;

    public function findBySlug(string $slug): ?EntityType;

    /** @return list<EntityType> */
    public function findAll(int $limit, int $offset): array;

    public function save(EntityType $entityType): int;

    public function update(EntityType $entityType): void;

    /**
     * Persist a new ordering for the current organization's entity types.
     *
     * @param list<int> $idsInOrder
     */
    public function reorder(array $idsInOrder): void;

    public function delete(int $id): void;
}
