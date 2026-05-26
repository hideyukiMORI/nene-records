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
            'SELECT id, name, slug, is_pinned, labels FROM entity_types WHERE id = ?',
            [$id],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function findBySlug(string $slug): ?EntityType
    {
        $row = $this->query->fetchOne(
            'SELECT id, name, slug, is_pinned, labels FROM entity_types WHERE slug = ?',
            [$slug],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    /** @return list<EntityType> */
    public function findAll(int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, name, slug, is_pinned, labels FROM entity_types ORDER BY id ASC LIMIT ? OFFSET ?',
            [$limit, $offset],
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function save(EntityType $entityType): int
    {
        $this->query->execute(
            'INSERT INTO entity_types (name, slug, is_pinned, labels) VALUES (?, ?, ?, ?)',
            [
                $entityType->name,
                $entityType->slug,
                $entityType->isPinned ? 1 : 0,
                $entityType->labels !== null ? json_encode($entityType->labels, JSON_UNESCAPED_UNICODE) : null,
            ],
        );

        return $this->query->lastInsertId();
    }

    public function update(EntityType $entityType): void
    {
        $this->query->execute(
            'UPDATE entity_types SET name = ?, slug = ?, is_pinned = ?, labels = ? WHERE id = ?',
            [
                $entityType->name,
                $entityType->slug,
                $entityType->isPinned ? 1 : 0,
                $entityType->labels !== null ? json_encode($entityType->labels, JSON_UNESCAPED_UNICODE) : null,
                $entityType->id,
            ],
        );
    }

    public function delete(int $id): void
    {
        $this->query->execute('DELETE FROM field_defs WHERE entity_type_id = ?', [$id]);
        $this->query->execute('DELETE FROM entity_types WHERE id = ?', [$id]);
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): EntityType
    {
        $labels = null;
        if (isset($row['labels']) && is_string($row['labels']) && $row['labels'] !== '') {
            $decoded = json_decode($row['labels'], true);
            if (is_array($decoded)) {
                /** @var array<string, string> $decoded */
                $labels = $decoded;
            }
        }

        return new EntityType(
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            isPinned: (bool) $row['is_pinned'],
            id: (int) $row['id'],
            labels: $labels,
        );
    }
}
