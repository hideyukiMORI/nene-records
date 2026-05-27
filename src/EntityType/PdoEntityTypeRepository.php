<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoEntityTypeRepository implements EntityTypeRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private RequestScopedHolder $orgId,
    ) {
    }

    public function findById(int $id): ?EntityType
    {
        $row = $this->query->fetchOne(
            'SELECT id, name, slug, is_pinned, labels, permalink_pattern, previous_permalink_pattern FROM entity_types WHERE id = ? AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function findBySlug(string $slug): ?EntityType
    {
        $row = $this->query->fetchOne(
            'SELECT id, name, slug, is_pinned, labels, permalink_pattern, previous_permalink_pattern FROM entity_types WHERE slug = ? AND organization_id = ?',
            [$slug, $this->orgId->get()],
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
            'SELECT id, name, slug, is_pinned, labels, permalink_pattern, previous_permalink_pattern FROM entity_types WHERE organization_id = ? ORDER BY id ASC LIMIT ? OFFSET ?',
            [$this->orgId->get(), $limit, $offset],
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function save(EntityType $entityType): int
    {
        $this->query->execute(
            'INSERT INTO entity_types (organization_id, name, slug, is_pinned, labels, permalink_pattern) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $this->orgId->get(),
                $entityType->name,
                $entityType->slug,
                $entityType->isPinned ? 1 : 0,
                $entityType->labels !== null ? json_encode($entityType->labels, JSON_UNESCAPED_UNICODE) : null,
                $entityType->permalinkPattern,
            ],
        );

        return $this->query->lastInsertId();
    }

    public function update(EntityType $entityType): void
    {
        $this->query->execute(
            'UPDATE entity_types SET name = ?, slug = ?, is_pinned = ?, labels = ?, permalink_pattern = ?, previous_permalink_pattern = ? WHERE id = ? AND organization_id = ?',
            [
                $entityType->name,
                $entityType->slug,
                $entityType->isPinned ? 1 : 0,
                $entityType->labels !== null ? json_encode($entityType->labels, JSON_UNESCAPED_UNICODE) : null,
                $entityType->permalinkPattern,
                $entityType->previousPermalinkPattern,
                $entityType->id,
                $this->orgId->get(),
            ],
        );
    }

    public function delete(int $id): void
    {
        $this->query->execute('DELETE FROM field_defs WHERE entity_type_id = ? AND organization_id = ?', [$id, $this->orgId->get()]);
        $this->query->execute('DELETE FROM entity_types WHERE id = ? AND organization_id = ?', [$id, $this->orgId->get()]);
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

        $permalinkPattern = isset($row['permalink_pattern']) && is_string($row['permalink_pattern']) && $row['permalink_pattern'] !== ''
            ? $row['permalink_pattern']
            : null;

        $previousPermalinkPattern = isset($row['previous_permalink_pattern']) && is_string($row['previous_permalink_pattern']) && $row['previous_permalink_pattern'] !== ''
            ? $row['previous_permalink_pattern']
            : null;

        return new EntityType(
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            isPinned: (bool) $row['is_pinned'],
            id: (int) $row['id'],
            labels: $labels,
            permalinkPattern: $permalinkPattern,
            previousPermalinkPattern: $previousPermalinkPattern,
        );
    }
}
