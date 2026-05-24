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

    private int $nextId;

    /** @param list<Entity> $seed */
    public function __construct(array $seed = [])
    {
        $this->entities = [];
        $this->tagSlugsByEntityId = [];
        $this->nextId = 1;

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

    public function findById(int $id): ?Entity
    {
        $entity = $this->entities[$id] ?? null;

        if ($entity === null || $entity->isDeleted) {
            return null;
        }

        return $entity;
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

            $active[] = $entity;
        }

        usort($active, static fn (Entity $a, Entity $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
    }

    public function countByCriteria(EntityListCriteria $criteria): int
    {
        return count($this->findByCriteria($criteria, PHP_INT_MAX, 0));
    }

    public function save(Entity $entity): int
    {
        $id = $this->nextId++;

        $this->entities[$id] = new Entity(
            id: $id,
            entityTypeId: $entity->entityTypeId,
        );

        return $id;
    }

    public function update(Entity $entity): void
    {
        $id = $entity->id;

        if ($id === null || !isset($this->entities[$id]) || $this->entities[$id]->isDeleted) {
            return;
        }

        $this->entities[$id] = $entity;
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
            isDeleted: true,
            deletedAt: new DateTimeImmutable('@1700000000'),
        );
    }
}
