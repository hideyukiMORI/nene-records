<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;
use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoEntityRepository implements EntityRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(int $id): ?Entity
    {
        $row = $this->query->fetchOne(
            <<<'SQL'
                SELECT id, entity_type_id, is_deleted, deleted_at
                FROM entities
                WHERE id = ? AND is_deleted = 0
                SQL,
            [$id],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    /** @return list<Entity> */
    public function findAll(int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            <<<'SQL'
                SELECT id, entity_type_id, is_deleted, deleted_at
                FROM entities
                WHERE is_deleted = 0
                ORDER BY id ASC
                LIMIT ? OFFSET ?
                SQL,
            [$limit, $offset],
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function save(Entity $entity): int
    {
        $this->query->execute(
            'INSERT INTO entities (entity_type_id) VALUES (?)',
            [$entity->entityTypeId],
        );

        return $this->query->lastInsertId();
    }

    public function update(Entity $entity): void
    {
        $id = $entity->id;

        if ($id === null) {
            throw new LogicException('Entity id must be set before update.');
        }

        $this->query->execute(
            <<<'SQL'
                UPDATE entities
                SET entity_type_id = ?
                WHERE id = ? AND is_deleted = 0
                SQL,
            [$entity->entityTypeId, $id],
        );
    }

    public function softDelete(int $id): void
    {
        $this->query->execute(
            <<<'SQL'
                UPDATE entities
                SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP
                WHERE id = ? AND is_deleted = 0
                SQL,
            [$id],
        );
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): Entity
    {
        $deletedRaw = $row['deleted_at'] ?? null;
        $deletedAt = ($deletedRaw !== null && $deletedRaw !== '') ? new DateTimeImmutable((string) $deletedRaw) : null;

        return new Entity(
            id: (int) $row['id'],
            entityTypeId: (int) $row['entity_type_id'],
            isDeleted: (bool) (int) $row['is_deleted'],
            deletedAt: $deletedAt,
        );
    }
}
