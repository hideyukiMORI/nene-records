<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface EntityRepositoryInterface
{
    public function findById(int $id): ?Entity;

    public function findBySlug(string $slug, int $entityTypeId): ?Entity;

    public function existsBySlug(string $slug, int $entityTypeId, ?int $excludeId = null): bool;

    /** Returns true if any ACTIVE (non-soft-deleted) entity belongs to this entity type. */
    public function existsActiveByEntityTypeId(int $entityTypeId): bool;

    /** @return list<Entity> */
    public function findAll(int $limit, int $offset): array;

    /** @return list<Entity> */
    public function findByCriteria(EntityListCriteria $criteria, int $limit, int $offset): array;

    public function countByCriteria(EntityListCriteria $criteria): int;

    /** @return list<EntityRevision> */
    public function findRevisionsByEntityId(int $entityId, int $limit, int $offset): array;

    public function save(Entity $entity): int;

    public function update(Entity $entity): void;

    /**
     * Marks the entity as deleted (does not physically remove rows).
     */
    public function softDelete(int $id): void;
}
