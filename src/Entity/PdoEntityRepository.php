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
        return $this->findByCriteria(new EntityListCriteria(), $limit, $offset);
    }

    /** @return list<Entity> */
    public function findByCriteria(EntityListCriteria $criteria, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildCriteriaWhere($criteria);
        $params[] = $limit;
        $params[] = $offset;

        $rows = $this->query->fetchAll(
            <<<SQL
                SELECT e.id, e.entity_type_id, e.is_deleted, e.deleted_at
                FROM entities e
                WHERE {$where}
                ORDER BY e.id ASC
                LIMIT ? OFFSET ?
                SQL,
            $params,
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function countByCriteria(EntityListCriteria $criteria): int
    {
        [$where, $params] = $this->buildCriteriaWhere($criteria);

        $row = $this->query->fetchOne(
            <<<SQL
                SELECT COUNT(*) AS total
                FROM entities e
                WHERE {$where}
                SQL,
            $params,
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildCriteriaWhere(EntityListCriteria $criteria): array
    {
        $conditions = ['e.is_deleted = 0'];
        $params = [];

        if ($criteria->entityTypeId !== null) {
            $conditions[] = 'e.entity_type_id = ?';
            $params[] = $criteria->entityTypeId;
        }

        if ($criteria->tagSlugs !== []) {
            $placeholders = implode(', ', array_fill(0, count($criteria->tagSlugs), '?'));
            $conditions[] = <<<SQL
                EXISTS (
                    SELECT 1
                    FROM entity_tags et
                    INNER JOIN tags t ON t.id = et.tag_id
                    WHERE et.entity_id = e.id AND t.slug IN ({$placeholders})
                )
                SQL;
            foreach ($criteria->tagSlugs as $slug) {
                $params[] = $slug;
            }
        }

        foreach ($criteria->relationFilters as $fieldKey => $targetEntityId) {
            $conditions[] = <<<'SQL'
                EXISTS (
                    SELECT 1
                    FROM entity_relations er
                    WHERE er.source_entity_id = e.id
                      AND er.field_key = ?
                      AND er.target_entity_id = ?
                )
                SQL;
            $params[] = $fieldKey;
            $params[] = $targetEntityId;
        }

        return [implode(' AND ', $conditions), $params];
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
