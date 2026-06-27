<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface EntityRepositoryInterface
{
    public function findById(int $id): ?Entity;

    public function findBySlug(string $slug, int $entityTypeId): ?Entity;

    /** Resolve a record by its custom permalink path (org-scoped), or null (#651). */
    public function findByPermalink(string $permalink): ?Entity;

    /**
     * Direct child records whose permalink is exactly one path segment below the
     * given parent (e.g. parent `/a/b` → `/a/b/x`, not `/a/b/x/y`). Published and
     * org-scoped, ordered by permalink. Powers the section child-list (#651 PR2).
     *
     * @return list<Entity>
     */
    public function findDirectChildrenByPermalink(string $parentPermalink, int $limit): array;

    /**
     * Every descendant record under a permalink prefix — all levels below
     * `/prefix/`, ANY status, org-scoped, ordered by permalink. Unlike
     * {@see findDirectChildrenByPermalink} this spans deeper levels and all
     * statuses. Powers the directory subtree move (#659).
     *
     * @return list<Entity>
     */
    public function findByPermalinkPrefix(string $prefix): array;

    /** Rewrite only a record's custom permalink (subtree move, #659). */
    public function updatePermalink(int $id, string $permalink): void;

    public function existsBySlug(string $slug, int $entityTypeId, ?int $excludeId = null): bool;

    /** True when another record in the org already owns this custom permalink (#651). */
    public function existsByPermalink(string $permalink, ?int $excludeId = null): bool;

    /** Returns true if any ACTIVE (non-soft-deleted) entity belongs to this entity type. */
    public function existsActiveByEntityTypeId(int $entityTypeId): bool;

    /** @return list<Entity> */
    public function findAll(int $limit, int $offset): array;

    /** @return list<Entity> */
    public function findByCriteria(EntityListCriteria $criteria, int $limit, int $offset): array;

    public function countByCriteria(EntityListCriteria $criteria): int;

    /** @return list<Entity> */
    public function findDueScheduled(): array;

    /** @return list<Entity> Most recently published entities, ordered by published_at DESC. */
    public function findRecentPublished(int $limit): array;

    /** @return array<int, int> entityTypeId => published count */
    public function countPublishedGroupedByEntityType(): array;

    /** @return array<int, int> entityTypeId => draft count */
    public function countDraftGroupedByEntityType(): array;

    /** @return list<EntityRevision> */
    public function findRevisionsByEntityId(int $entityId, int $limit, int $offset): array;

    public function save(Entity $entity): int;

    public function update(Entity $entity): void;

    /**
     * Marks the entity as deleted (does not physically remove rows).
     */
    public function softDelete(int $id): void;
}
