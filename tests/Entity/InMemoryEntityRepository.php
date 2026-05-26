<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use DateTimeImmutable;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityListCriteria;
use NeNeRecords\Entity\EntityRepositoryInterface;

final class InMemoryEntityRepository implements EntityRepositoryInterface
{
    /** @var array<int, Entity> */
    private array $entities;

    /** @var array<int, list<string>> entityId => tag slugs */
    private array $tagSlugsByEntityId;

    /** @var array<int, array<string, list<int>>> entityId => fieldKey => target entity ids */
    private array $relationsByEntityId;

    /** @var list<\NeNeRecords\Entity\EntityRevision> */
    private array $revisions;

    private int $nextId;
    private int $nextRevisionId;

    /** @param list<Entity> $seed */
    public function __construct(array $seed = [])
    {
        $this->entities = [];
        $this->tagSlugsByEntityId = [];
        $this->relationsByEntityId = [];
        $this->revisions = [];
        $this->nextId = 1;
        $this->nextRevisionId = 1;

        foreach ($seed as $entity) {
            $id = $entity->id;
            if ($id !== null) {
                $this->entities[$id] = $entity;
                $this->nextId = max($this->nextId, $id + 1);
            }
        }
    }

    /** @param list<string> $tagSlugs */
    public function setTagSlugsForEntity(int $entityId, array $tagSlugs): void
    {
        $this->tagSlugsByEntityId[$entityId] = $tagSlugs;
    }

    public function setRelationForEntity(int $entityId, string $fieldKey, int $targetEntityId): void
    {
        $this->relationsByEntityId[$entityId][$fieldKey][] = $targetEntityId;
    }

    public function findById(int $id): ?Entity
    {
        $entity = $this->entities[$id] ?? null;

        if ($entity === null || $entity->isDeleted) {
            return null;
        }

        return $entity;
    }

    public function findBySlug(string $slug, int $entityTypeId): ?Entity
    {
        foreach ($this->entities as $entity) {
            if ($entity->isDeleted) {
                continue;
            }

            if ($entity->slug === $slug && $entity->entityTypeId === $entityTypeId) {
                return $entity;
            }
        }

        return null;
    }

    public function existsBySlug(string $slug, int $entityTypeId, ?int $excludeId = null): bool
    {
        foreach ($this->entities as $entity) {
            if ($entity->isDeleted) {
                continue;
            }

            if ($entity->slug === $slug && $entity->entityTypeId === $entityTypeId) {
                if ($excludeId !== null && $entity->id === $excludeId) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    public function existsActiveByEntityTypeId(int $entityTypeId): bool
    {
        foreach ($this->entities as $entity) {
            if ($entity->entityTypeId === $entityTypeId && !$entity->isDeleted) {
                return true;
            }
        }

        return false;
    }

    /** @return list<Entity> */
    public function findAll(int $limit, int $offset): array
    {
        return $this->findByCriteria(new EntityListCriteria(), $limit, $offset);
    }

    /** @return list<Entity> */
    public function findByCriteria(EntityListCriteria $criteria, int $limit, int $offset): array
    {
        $active = [];

        foreach ($this->entities as $entity) {
            if ($entity->isDeleted) {
                continue;
            }

            if ($criteria->entityTypeId !== null && $entity->entityTypeId !== $criteria->entityTypeId) {
                continue;
            }

            if ($criteria->status !== null && $entity->status !== $criteria->status) {
                continue;
            }

            if ($criteria->tagSlugs !== []) {
                $entityId = $entity->id ?? 0;
                $entityTagSlugs = $this->tagSlugsByEntityId[$entityId] ?? [];
                $matches = false;

                foreach ($criteria->tagSlugs as $slug) {
                    if (in_array($slug, $entityTagSlugs, true)) {
                        $matches = true;
                        break;
                    }
                }

                if (!$matches) {
                    continue;
                }
            }

            if ($criteria->relationFilters !== []) {
                $entityId = $entity->id ?? 0;

                foreach ($criteria->relationFilters as $fieldKey => $targetEntityId) {
                    $linkedTargetIds = $this->relationsByEntityId[$entityId][$fieldKey] ?? [];

                    if (!in_array($targetEntityId, $linkedTargetIds, true)) {
                        continue 2;
                    }
                }
            }

            $active[] = $entity;
        }

        usort($active, static fn (Entity $a, Entity $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
    }

    public function countByCriteria(EntityListCriteria $criteria): int
    {
        return count($this->findByCriteria($criteria, PHP_INT_MAX, 0));
    }

    /** @return list<Entity> */
    public function findDueScheduled(): array
    {
        $now = new DateTimeImmutable();
        $result = [];

        foreach ($this->entities as $entity) {
            if ($entity->isDeleted) {
                continue;
            }

            if ($entity->status !== \NeNeRecords\Entity\EntityStatus::Scheduled) {
                continue;
            }

            if ($entity->scheduledAt !== null && $entity->scheduledAt <= $now) {
                $result[] = $entity;
            }
        }

        return $result;
    }

    /** @return list<\NeNeRecords\Entity\EntityRevision> */
    public function findRevisionsByEntityId(int $entityId, int $limit, int $offset): array
    {
        $filtered = array_values(array_filter(
            $this->revisions,
            static fn (\NeNeRecords\Entity\EntityRevision $r): bool => $r->entityId === $entityId,
        ));

        // Newest first
        $filtered = array_reverse($filtered);

        return array_slice($filtered, $offset, $limit);
    }

    public function save(Entity $entity): int
    {
        $id = $this->nextId++;

        $this->entities[$id] = new Entity(
            id: $id,
            entityTypeId: $entity->entityTypeId,
            slug: $entity->slug,
            status: $entity->status,
            publishedAt: $entity->publishedAt,
            metaTitle: $entity->metaTitle,
            metaDescription: $entity->metaDescription,
            scheduledAt: $entity->scheduledAt,
        );

        $this->revisions[] = new \NeNeRecords\Entity\EntityRevision(
            entityId: $id,
            action: \NeNeRecords\Entity\EntityRevisionAction::Created,
            status: $entity->status->value,
            previousStatus: null,
            slug: $entity->slug,
            previousSlug: null,
            actorUserId: null,
            createdAt: date('Y-m-d H:i:s'),
            id: $this->nextRevisionId++,
        );

        return $id;
    }

    public function update(Entity $entity): void
    {
        $id = $entity->id;

        if ($id === null || !isset($this->entities[$id]) || $this->entities[$id]->isDeleted) {
            return;
        }

        $existing = $this->entities[$id];
        $this->entities[$id] = $entity;

        $this->revisions[] = new \NeNeRecords\Entity\EntityRevision(
            entityId: $id,
            action: \NeNeRecords\Entity\EntityRevisionAction::Updated,
            status: $entity->status->value,
            previousStatus: $existing->status->value,
            slug: $entity->slug,
            previousSlug: $existing->slug,
            actorUserId: null,
            createdAt: date('Y-m-d H:i:s'),
            id: $this->nextRevisionId++,
        );
    }

    public function softDelete(int $id): void
    {
        $entity = $this->entities[$id] ?? null;

        if ($entity === null || $entity->isDeleted) {
            return;
        }

        $this->entities[$id] = new Entity(
            id: $id,
            entityTypeId: $entity->entityTypeId,
            status: $entity->status,
            isDeleted: true,
            deletedAt: new DateTimeImmutable('@1700000000'),
        );

        $this->revisions[] = new \NeNeRecords\Entity\EntityRevision(
            entityId: $id,
            action: \NeNeRecords\Entity\EntityRevisionAction::Deleted,
            status: $entity->status->value,
            previousStatus: $entity->status->value,
            slug: $entity->slug,
            previousSlug: $entity->slug,
            actorUserId: null,
            createdAt: date('Y-m-d H:i:s'),
            id: $this->nextRevisionId++,
        );
    }
}
