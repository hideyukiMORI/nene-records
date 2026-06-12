<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityType;

use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;

final class InMemoryEntityTypeRepository implements EntityTypeRepositoryInterface
{
    /** @var array<int, EntityType> */
    private array $byId;

    /** @var array<string, int> */
    private array $slugToId;

    private int $nextId;

    /** @param list<EntityType> $seed */
    public function __construct(array $seed = [])
    {
        $this->byId = [];
        $this->slugToId = [];
        $this->nextId = 1;

        foreach ($seed as $entityType) {
            if ($entityType->id !== null) {
                $id = $entityType->id;
                $stored = new EntityType(
                    name: $entityType->name,
                    slug: $entityType->slug,
                    isPinned: $entityType->isPinned,
                    id: $id,
                    labels: $entityType->labels,
                    permalinkPattern: $entityType->permalinkPattern,
                    previousPermalinkPattern: $entityType->previousPermalinkPattern,
                    displayOrder: $entityType->displayOrder,
                );
                $this->byId[$id] = $stored;
                $this->slugToId[$stored->slug] = $id;
                $this->nextId = max($this->nextId, $id + 1);
            }
        }
    }

    public function findById(int $id): ?EntityType
    {
        return $this->byId[$id] ?? null;
    }

    public function findBySlug(string $slug): ?EntityType
    {
        $id = $this->slugToId[$slug] ?? null;

        if ($id === null) {
            return null;
        }

        return $this->byId[$id] ?? null;
    }

    /** @return list<EntityType> */
    public function findAll(int $limit, int $offset): array
    {
        $types = array_values($this->byId);
        usort(
            $types,
            static fn (EntityType $a, EntityType $b): int => [$a->displayOrder, $a->id ?? 0] <=> [$b->displayOrder, $b->id ?? 0],
        );

        return array_slice($types, $offset, $limit);
    }

    public function save(EntityType $entityType): int
    {
        $id = $this->nextId++;
        $stored = new EntityType(
            name: $entityType->name,
            slug: $entityType->slug,
            isPinned: $entityType->isPinned,
            id: $id,
            labels: $entityType->labels,
            permalinkPattern: $entityType->permalinkPattern,
            previousPermalinkPattern: $entityType->previousPermalinkPattern,
        );
        $this->byId[$id] = $stored;
        $this->slugToId[$stored->slug] = $id;

        return $id;
    }

    public function update(EntityType $entityType): void
    {
        $id = $entityType->id;

        if ($id === null || !isset($this->byId[$id])) {
            return;
        }

        $old = $this->byId[$id];
        unset($this->slugToId[$old->slug]);

        $this->slugToId[$entityType->slug] = $id;
        $this->byId[$id] = $entityType;
    }

    /** @param list<int> $idsInOrder */
    public function reorder(array $idsInOrder): void
    {
        $position = 0;
        foreach ($idsInOrder as $id) {
            $existing = $this->byId[$id] ?? null;
            if ($existing !== null) {
                $this->byId[$id] = new EntityType(
                    name: $existing->name,
                    slug: $existing->slug,
                    isPinned: $existing->isPinned,
                    id: $existing->id,
                    labels: $existing->labels,
                    permalinkPattern: $existing->permalinkPattern,
                    previousPermalinkPattern: $existing->previousPermalinkPattern,
                    displayOrder: $position,
                );
            }
            $position++;
        }
    }

    public function delete(int $id): void
    {
        $entityType = $this->byId[$id] ?? null;

        if ($entityType === null) {
            return;
        }

        unset($this->slugToId[$entityType->slug]);
        unset($this->byId[$id]);
    }
}
