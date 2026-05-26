<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoEntityTypeRepository implements EntityTypeRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(int $id): ?EntityType
    {
        $row = $this->query->fetchOne(
            'SELECT id, name, slug, is_pinned FROM entity_types WHERE id = ?',
            [$id],
        );

        if ($row === null) {
            return null;
        }

        return new EntityType(
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            isPinned: (bool) $row['is_pinned'],
            id: (int) $row['id'],
        );
    }

    public function findBySlug(string $slug): ?EntityType
    {
        $row = $this->query->fetchOne(
            'SELECT id, name, slug, is_pinned FROM entity_types WHERE slug = ?',
            [$slug],
        );

        if ($row === null) {
            return null;
        }

        return new EntityType(
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            isPinned: (bool) $row['is_pinned'],
            id: (int) $row['id'],
        );
    }

    /** @return list<EntityType> */
    public function findAll(int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, name, slug, is_pinned FROM entity_types ORDER BY id ASC LIMIT ? OFFSET ?',
            [$limit, $offset],
        );

        return array_map(
            static fn (array $row) => new EntityType(
                name: (string) $row['name'],
                slug: (string) $row['slug'],
                isPinned: (bool) $row['is_pinned'],
                id: (int) $row['id'],
            ),
            $rows,
        );
    }

    public function save(EntityType $entityType): int
    {
        $this->query->execute(
            'INSERT INTO entity_types (name, slug, is_pinned) VALUES (?, ?, ?)',
            [$entityType->name, $entityType->slug, $entityType->isPinned ? 1 : 0],
        );

        return $this->query->lastInsertId();
    }

    public function update(EntityType $entityType): void
    {
        $this->query->execute(
            'UPDATE entity_types SET name = ?, slug = ?, is_pinned = ? WHERE id = ?',
            [$entityType->name, $entityType->slug, $entityType->isPinned ? 1 : 0, $entityType->id],
        );
    }

    public function delete(int $id): void
    {
        $this->query->execute('DELETE FROM field_defs WHERE entity_type_id = ?', [$id]);
        $this->query->execute('DELETE FROM entity_types WHERE id = ?', [$id]);
    }
}
