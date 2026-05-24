<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use DateTimeImmutable;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityRepositoryInterface;

final class InMemoryEntityRepository implements EntityRepositoryInterface
{
    /** @var array<int, Entity> */
    private array $entities;

    private int $nextId;

    /** @param list<Entity> $seed */
    public function __construct(array $seed = [])
    {
        $this->entities = [];
        $this->nextId = 1;

        foreach ($seed as $entity) {
            $id = $entity->id;
            if ($id !== null) {
                $this->entities[$id] = $entity;
                $this->nextId = max($this->nextId, $id + 1);
            }
        }
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
        $active = [];

        foreach ($this->entities as $entity) {
            if (!$entity->isDeleted) {
                $active[] = $entity;
            }
        }

        usort($active, static fn (Entity $a, Entity $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));

        return array_slice($active, $offset, $limit);
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
