<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;
use DateTimeInterface;
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
                SELECT id, entity_type_id, slug, status, published_at, scheduled_at, is_deleted, deleted_at, meta_title, meta_description
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

    public function findBySlug(string $slug, int $entityTypeId): ?Entity
    {
        $row = $this->query->fetchOne(
            <<<'SQL'
                SELECT id, entity_type_id, slug, status, published_at, scheduled_at, is_deleted, deleted_at, meta_title, meta_description
                FROM entities
                WHERE slug = ? AND entity_type_id = ? AND is_deleted = 0
                SQL,
            [$slug, $entityTypeId],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function existsBySlug(string $slug, int $entityTypeId, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $row = $this->query->fetchOne(
                'SELECT id FROM entities WHERE slug = ? AND entity_type_id = ? AND id != ? AND is_deleted = 0',
                [$slug, $entityTypeId, $excludeId],
            );
        } else {
            $row = $this->query->fetchOne(
                'SELECT id FROM entities WHERE slug = ? AND entity_type_id = ? AND is_deleted = 0',
                [$slug, $entityTypeId],
            );
        }

        return $row !== null;
    }

    public function existsActiveByEntityTypeId(int $entityTypeId): bool
    {
        $row = $this->query->fetchOne(
            'SELECT id FROM entities WHERE entity_type_id = ? AND is_deleted = 0 LIMIT 1',
            [$entityTypeId],
        );

        return $row !== null;
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
                SELECT e.id, e.entity_type_id, e.slug, e.status, e.published_at, e.scheduled_at, e.is_deleted, e.deleted_at, e.meta_title, e.meta_description
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

        if ($criteria->status !== null) {
            $conditions[] = 'e.status = ?';
            $params[] = $criteria->status->value;
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

        if ($criteria->q !== null && $criteria->q !== '') {
            $like = '%' . $criteria->q . '%';
            $conditions[] = <<<'SQL'
                (
                    e.slug LIKE ?
                    OR EXISTS (
                        SELECT 1 FROM text_fields tf
                        WHERE tf.entity_id = e.id
                          AND tf.is_deleted = 0
                          AND tf.value LIKE ?
                    )
                )
                SQL;
            $params[] = $like;
            $params[] = $like;
        }

        return [implode(' AND ', $conditions), $params];
    }

    /** @return list<EntityRevision> */
    public function findRevisionsByEntityId(int $entityId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, entity_id, action, status, previous_status, slug, previous_slug, actor_user_id, created_at
             FROM entity_revisions
             WHERE entity_id = ?
             ORDER BY id DESC
             LIMIT ? OFFSET ?',
            [$entityId, $limit, $offset],
        );

        return array_map($this->mapRevision(...), $rows);
    }

    public function save(Entity $entity): int
    {
        $publishedAt = $entity->publishedAt?->format(DateTimeInterface::ATOM);
        $scheduledAt = $entity->scheduledAt?->format(DateTimeInterface::ATOM);
        $now = date('Y-m-d H:i:s');

        $this->query->execute(
            'INSERT INTO entities (entity_type_id, slug, status, published_at, scheduled_at, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$entity->entityTypeId, $entity->slug, $entity->status->value, $publishedAt, $scheduledAt, $entity->metaTitle, $entity->metaDescription],
        );

        $id = $this->query->lastInsertId();

        $this->query->execute(
            'INSERT INTO entity_revisions (entity_id, action, status, previous_status, slug, previous_slug, actor_user_id, created_at)
             VALUES (?, ?, ?, NULL, ?, NULL, NULL, ?)',
            [$id, EntityRevisionAction::Created->value, $entity->status->value, $entity->slug, $now],
        );

        return $id;
    }

    public function update(Entity $entity): void
    {
        $id = $entity->id;

        if ($id === null) {
            throw new LogicException('Entity id must be set before update.');
        }

        $existing = $this->findById($id);
        $now = date('Y-m-d H:i:s');
        $publishedAt = $entity->publishedAt?->format(DateTimeInterface::ATOM);
        $scheduledAt = $entity->scheduledAt?->format(DateTimeInterface::ATOM);

        $this->query->execute(
            <<<'SQL'
                UPDATE entities
                SET entity_type_id = ?, slug = ?, status = ?, published_at = ?, scheduled_at = ?, meta_title = ?, meta_description = ?
                WHERE id = ? AND is_deleted = 0
                SQL,
            [$entity->entityTypeId, $entity->slug, $entity->status->value, $publishedAt, $scheduledAt, $entity->metaTitle, $entity->metaDescription, $id],
        );

        $this->query->execute(
            'INSERT INTO entity_revisions (entity_id, action, status, previous_status, slug, previous_slug, actor_user_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NULL, ?)',
            [
                $id,
                EntityRevisionAction::Updated->value,
                $entity->status->value,
                $existing?->status->value,
                $entity->slug,
                $existing?->slug,
                $now,
            ],
        );
    }

    public function softDelete(int $id): void
    {
        $existing = $this->findById($id);
        $now = date('Y-m-d H:i:s');

        $this->query->execute(
            <<<'SQL'
                UPDATE entities
                SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP
                WHERE id = ? AND is_deleted = 0
                SQL,
            [$id],
        );

        if ($existing !== null) {
            $this->query->execute(
                'INSERT INTO entity_revisions (entity_id, action, status, previous_status, slug, previous_slug, actor_user_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NULL, ?)',
                [
                    $id,
                    EntityRevisionAction::Deleted->value,
                    $existing->status->value,
                    $existing->status->value,
                    $existing->slug,
                    $existing->slug,
                    $now,
                ],
            );
        }
    }

    /** @param array<string, mixed> $row */
    private function mapRevision(array $row): EntityRevision
    {
        return new EntityRevision(
            entityId: (int) $row['entity_id'],
            action: EntityRevisionAction::from((string) $row['action']),
            status: (string) $row['status'],
            previousStatus: $row['previous_status'] !== null ? (string) $row['previous_status'] : null,
            slug: $row['slug'] !== null ? (string) $row['slug'] : null,
            previousSlug: $row['previous_slug'] !== null ? (string) $row['previous_slug'] : null,
            actorUserId: $row['actor_user_id'] !== null ? (int) $row['actor_user_id'] : null,
            createdAt: (string) $row['created_at'],
            id: (int) $row['id'],
        );
    }

    /** @return list<Entity> */
    public function findDueScheduled(): array
    {
        $rows = $this->query->fetchAll(
            <<<'SQL'
                SELECT id, entity_type_id, slug, status, published_at, scheduled_at, is_deleted, deleted_at, meta_title, meta_description
                FROM entities
                WHERE status = 'scheduled' AND scheduled_at <= CURRENT_TIMESTAMP AND is_deleted = 0
                SQL,
            [],
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function findRecentPublished(int $limit): array
    {
        $rows = $this->query->fetchAll(
            <<<SQL
                SELECT id, entity_type_id, slug, status, published_at, scheduled_at, is_deleted, deleted_at, meta_title, meta_description
                FROM entities
                WHERE status = 'published' AND is_deleted = 0
                ORDER BY published_at DESC
                LIMIT ?
                SQL,
            [$limit],
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function countPublishedGroupedByEntityType(): array
    {
        $rows = $this->query->fetchAll(
            <<<'SQL'
                SELECT entity_type_id, COUNT(*) AS cnt
                FROM entities
                WHERE status = 'published' AND is_deleted = 0
                GROUP BY entity_type_id
                SQL,
            [],
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['entity_type_id']] = (int) $row['cnt'];
        }

        return $result;
    }

    public function countDraftGroupedByEntityType(): array
    {
        $rows = $this->query->fetchAll(
            <<<'SQL'
                SELECT entity_type_id, COUNT(*) AS cnt
                FROM entities
                WHERE status = 'draft' AND is_deleted = 0
                GROUP BY entity_type_id
                SQL,
            [],
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['entity_type_id']] = (int) $row['cnt'];
        }

        return $result;
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): Entity
    {
        $deletedRaw = $row['deleted_at'] ?? null;
        $deletedAt = ($deletedRaw !== null && $deletedRaw !== '') ? new DateTimeImmutable((string) $deletedRaw) : null;

        $publishedRaw = $row['published_at'] ?? null;
        $publishedAt = ($publishedRaw !== null && $publishedRaw !== '') ? new DateTimeImmutable((string) $publishedRaw) : null;

        $scheduledRaw = $row['scheduled_at'] ?? null;
        $scheduledAt = ($scheduledRaw !== null && $scheduledRaw !== '') ? new DateTimeImmutable((string) $scheduledRaw) : null;

        $slugRaw = $row['slug'] ?? null;
        $slug = ($slugRaw !== null && $slugRaw !== '') ? (string) $slugRaw : null;

        $metaTitleRaw = $row['meta_title'] ?? null;
        $metaTitle = ($metaTitleRaw !== null && $metaTitleRaw !== '') ? (string) $metaTitleRaw : null;

        $metaDescriptionRaw = $row['meta_description'] ?? null;
        $metaDescription = ($metaDescriptionRaw !== null && $metaDescriptionRaw !== '') ? (string) $metaDescriptionRaw : null;

        return new Entity(
            id: (int) $row['id'],
            entityTypeId: (int) $row['entity_type_id'],
            slug: $slug,
            status: EntityStatus::from((string) ($row['status'] ?? EntityStatus::Draft->value)),
            publishedAt: $publishedAt,
            isDeleted: (bool) (int) $row['is_deleted'],
            deletedAt: $deletedAt,
            metaTitle: $metaTitle,
            metaDescription: $metaDescription,
            scheduledAt: $scheduledAt,
        );
    }
}
