<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface EntityRepositoryInterface
{
    public function findById(int $id): ?Entity;

    /** @return list<Entity> */
    public function findAll(int $limit, int $offset): array;

    public function save(Entity $entity): int;

    public function update(Entity $entity): void;

    /**
     * Marks the entity as deleted (does not physically remove rows).
     */
    public function softDelete(int $id): void;
}
