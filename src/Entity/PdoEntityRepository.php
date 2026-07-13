<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoEntityRepository implements EntityRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private RequestScopedHolder $orgId,
        private ClockInterface $clock,
    ) {
    }

    public function findById(int $id): ?Entity
    {
        $row = $this->query->fetchOne(
            <<<'SQL'
                SELECT id, entity_type_id, slug, permalink, layout, show_comments, show_related, status, published_at, scheduled_at, is_deleted, created_at, updated_at, deleted_at, meta_title, meta_description
                FROM entities
                WHERE id = ? AND organization_id = ? AND is_deleted = 0
                SQL,
            [$id, $this->orgId->get()],
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
                SELECT id, entity_type_id, slug, permalink, layout, show_comments, show_related, status, published_at, scheduled_at, is_deleted, created_at, updated_at, deleted_at, meta_title, meta_description
                FROM entities
                WHERE slug = ? AND entity_type_id = ? AND organization_id = ? AND is_deleted = 0
                SQL,
            [$slug, $entityTypeId, $this->orgId->get()],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function findByPermalink(string $permalink): ?Entity
    {
        $row = $this->query->fetchOne(
            <<<'SQL'
                SELECT id, entity_type_id, slug, permalink, layout, show_comments, show_related, status, published_at, scheduled_at, is_deleted, created_at, updated_at, deleted_at, meta_title, meta_description
                FROM entities
                WHERE permalink = ? AND organization_id = ? AND is_deleted = 0
                SQL,
            [$permalink, $this->orgId->get()],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    /** @return list<Entity> */
    public function findDirectChildrenByPermalink(string $parentPermalink, int $limit): array
    {
        // `/a/b` → children `/a/b/x` (LIKE `/a/b/%`) but not grandchildren
        // `/a/b/x/y` (NOT LIKE `/a/b/%/%`). Permalinks are kebab + slash only, so
        // the LIKE wildcards never collide with a literal `%`/`_` in the data.
        $prefix = $parentPermalink . '/';
        $rows = $this->query->fetchAll(
            <<<'SQL'
                SELECT id, entity_type_id, slug, permalink, layout, show_comments, show_related, status, published_at, scheduled_at, is_deleted, created_at, updated_at, deleted_at, meta_title, meta_description, menu_order
                FROM entities
                WHERE organization_id = ?
                  AND is_deleted = 0
                  AND status = ?
                  AND permalink LIKE ?
                  AND permalink NOT LIKE ?
                ORDER BY menu_order ASC, permalink ASC
                LIMIT ?
                SQL,
            [$this->orgId->get(), EntityStatus::Published->value, $prefix . '%', $prefix . '%/%', $limit],
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    /** @return list<Entity> */
    public function findByPermalinkPrefix(string $prefix): array
    {
        // Every descendant under `/prefix/` (all levels, any status), org-scoped.
        // Permalinks are kebab + slash only, so the LIKE wildcard never collides
        // with a literal `%`/`_` in the data.
        $rows = $this->query->fetchAll(
            <<<'SQL'
                SELECT id, entity_type_id, slug, permalink, layout, show_comments, show_related, status, published_at, scheduled_at, is_deleted, created_at, updated_at, deleted_at, meta_title, meta_description
                FROM entities
                WHERE organization_id = ?
                  AND is_deleted = 0
                  AND permalink LIKE ?
                ORDER BY permalink ASC
                SQL,
            [$this->orgId->get(), $prefix . '/%'],
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function updatePermalink(int $id, string $permalink): void
    {
        $this->query->execute(
            'UPDATE entities SET permalink = ?, updated_at = NOW() WHERE id = ? AND organization_id = ?',
            [$permalink, $id, $this->orgId->get()],
        );
    }

    public function updateMenuOrder(int $id, int $menuOrder): void
    {
        $this->query->execute(
            'UPDATE entities SET menu_order = ?, updated_at = NOW() WHERE id = ? AND organization_id = ?',
            [$menuOrder, $id, $this->orgId->get()],
        );
    }

    public function existsByPermalink(string $permalink, ?int $excludeId = null): bool
    {
        // No is_deleted filter: the unique index spans every row, so the pre-check
        // must too, otherwise a soft-deleted twin would slip past here and surface
        // as a raw constraint 500 instead of a clean conflict.
        if ($excludeId !== null) {
            $row = $this->query->fetchOne(
                'SELECT id FROM entities WHERE permalink = ? AND organization_id = ? AND id != ?',
                [$permalink, $this->orgId->get(), $excludeId],
            );
        } else {
            $row = $this->query->fetchOne(
                'SELECT id FROM entities WHERE permalink = ? AND organization_id = ?',
                [$permalink, $this->orgId->get()],
            );
        }

        return $row !== null;
    }

    public function existsBySlug(string $slug, int $entityTypeId, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $row = $this->query->fetchOne(
                'SELECT id FROM entities WHERE slug = ? AND entity_type_id = ? AND organization_id = ? AND id != ? AND is_deleted = 0',
                [$slug, $entityTypeId, $this->orgId->get(), $excludeId],
            );
        } else {
            $row = $this->query->fetchOne(
                'SELECT id FROM entities WHERE slug = ? AND entity_type_id = ? AND organization_id = ? AND is_deleted = 0',
                [$slug, $entityTypeId, $this->orgId->get()],
            );
        }

        return $row !== null;
    }

    public function existsActiveByEntityTypeId(int $entityTypeId): bool
    {
        $row = $this->query->fetchOne(
            'SELECT id FROM entities WHERE entity_type_id = ? AND organization_id = ? AND is_deleted = 0 LIMIT 1',
            [$entityTypeId, $this->orgId->get()],
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
        $orderBy = $this->buildOrderBy($criteria);
        $params[] = $limit;
        $params[] = $offset;

        $titleJoin = $criteria->sortKey === EntitySortKey::Title
            ? "LEFT JOIN text_fields tf_sort ON tf_sort.entity_id = e.id AND tf_sort.field_key = 'title' AND tf_sort.is_deleted = 0"
            : '';

        $rows = $this->query->fetchAll(
            <<<SQL
                SELECT e.id, e.entity_type_id, e.slug, e.permalink, e.layout, e.show_comments, e.show_related, e.status, e.published_at, e.scheduled_at, e.is_deleted, e.created_at, e.updated_at, e.deleted_at, e.meta_title, e.meta_description, e.menu_order
                FROM entities e
                {$titleJoin}
                WHERE {$where}
                {$orderBy}
                LIMIT ? OFFSET ?
                SQL,
            $params,
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    private function buildOrderBy(EntityListCriteria $criteria): string
    {
        $dir = $criteria->sortOrder === EntitySortOrder::Asc ? 'ASC' : 'DESC';

        return match ($criteria->sortKey) {
            EntitySortKey::Id => "ORDER BY e.id {$dir}",
            EntitySortKey::PublishedAt => $criteria->sortOrder === EntitySortOrder::Asc
                ? 'ORDER BY e.published_at IS NULL ASC, e.published_at ASC'
                : 'ORDER BY e.published_at IS NULL DESC, e.published_at DESC',
            EntitySortKey::Title => "ORDER BY COALESCE(tf_sort.value, '') {$dir}, e.id DESC",
        };
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
        $conditions = ['e.is_deleted = 0', 'e.organization_id = ?'];
        $params = [$this->orgId->get()];

        if ($criteria->entityTypeId !== null) {
            $conditions[] = 'e.entity_type_id = ?';
            $params[] = $criteria->entityTypeId;
        }

        if ($criteria->hasPermalink) {
            $conditions[] = "(e.permalink IS NOT NULL AND e.permalink != '')";
        }

        if ($criteria->publishedOnly) {
            // Anonymous callers: force published-only and ignore any client `status`
            // filter (so `?status=draft` cannot surface unpublished records). See #828.
            $conditions[] = 'e.status = ?';
            $params[] = EntityStatus::Published->value;
        } elseif ($criteria->status !== null) {
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

        if ($criteria->publishedFrom !== null) {
            $conditions[] = 'e.published_at >= ?';
            $params[] = $criteria->publishedFrom;
        }

        if ($criteria->publishedToExclusive !== null) {
            $conditions[] = 'e.published_at < ?';
            $params[] = $criteria->publishedToExclusive;
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
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->query->execute(
            'INSERT INTO entities (organization_id, entity_type_id, slug, permalink, layout, show_comments, show_related, status, published_at, scheduled_at, created_at, updated_at, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$this->orgId->get(), $entity->entityTypeId, $entity->slug, $entity->permalink, $entity->layout, self::toDbBool($entity->showComments), self::toDbBool($entity->showRelated), $entity->status->value, $publishedAt, $scheduledAt, $now, $now, $entity->metaTitle, $entity->metaDescription],
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
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $publishedAt = $entity->publishedAt?->format(DateTimeInterface::ATOM);
        $scheduledAt = $entity->scheduledAt?->format(DateTimeInterface::ATOM);

        $this->query->execute(
            <<<'SQL'
                UPDATE entities
                SET entity_type_id = ?, slug = ?, permalink = ?, layout = ?, show_comments = ?, show_related = ?, status = ?, published_at = ?, scheduled_at = ?, updated_at = ?, meta_title = ?, meta_description = ?
                WHERE id = ? AND organization_id = ? AND is_deleted = 0
                SQL,
            [$entity->entityTypeId, $entity->slug, $entity->permalink, $entity->layout, self::toDbBool($entity->showComments), self::toDbBool($entity->showRelated), $entity->status->value, $publishedAt, $scheduledAt, $now, $entity->metaTitle, $entity->metaDescription, $id, $this->orgId->get()],
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
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->query->execute(
            <<<'SQL'
                UPDATE entities
                SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP
                WHERE id = ? AND organization_id = ? AND is_deleted = 0
                SQL,
            [$id, $this->orgId->get()],
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
                SELECT id, entity_type_id, slug, permalink, layout, show_comments, show_related, status, published_at, scheduled_at, is_deleted, created_at, updated_at, deleted_at, meta_title, meta_description
                FROM entities
                WHERE status = 'scheduled' AND scheduled_at <= CURRENT_TIMESTAMP AND organization_id = ? AND is_deleted = 0
                SQL,
            [$this->orgId->get()],
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function findRecentPublished(int $limit): array
    {
        $rows = $this->query->fetchAll(
            <<<SQL
                SELECT id, entity_type_id, slug, permalink, layout, show_comments, show_related, status, published_at, scheduled_at, is_deleted, created_at, updated_at, deleted_at, meta_title, meta_description
                FROM entities
                WHERE status = 'published' AND organization_id = ? AND is_deleted = 0
                ORDER BY published_at DESC
                LIMIT ?
                SQL,
            [$this->orgId->get(), $limit],
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function countPublishedGroupedByEntityType(): array
    {
        $rows = $this->query->fetchAll(
            <<<'SQL'
                SELECT entity_type_id, COUNT(*) AS cnt
                FROM entities
                WHERE status = 'published' AND organization_id = ? AND is_deleted = 0
                GROUP BY entity_type_id
                SQL,
            [$this->orgId->get()],
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
                WHERE status = 'draft' AND organization_id = ? AND is_deleted = 0
                GROUP BY entity_type_id
                SQL,
            [$this->orgId->get()],
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

        $createdRaw = $row['created_at'] ?? null;
        $createdAt = ($createdRaw !== null && $createdRaw !== '') ? new DateTimeImmutable((string) $createdRaw) : null;

        $updatedRaw = $row['updated_at'] ?? null;
        $updatedAt = ($updatedRaw !== null && $updatedRaw !== '') ? new DateTimeImmutable((string) $updatedRaw) : null;

        $slugRaw = $row['slug'] ?? null;
        $slug = ($slugRaw !== null && $slugRaw !== '') ? (string) $slugRaw : null;

        $permalinkRaw = $row['permalink'] ?? null;
        $permalink = ($permalinkRaw !== null && $permalinkRaw !== '') ? (string) $permalinkRaw : null;

        $layoutRaw = $row['layout'] ?? null;
        $layout = ($layoutRaw !== null && $layoutRaw !== '') ? (string) $layoutRaw : null;

        $showCommentsRaw = $row['show_comments'] ?? null;
        $showComments = $showCommentsRaw !== null ? (bool) (int) $showCommentsRaw : null;

        $showRelatedRaw = $row['show_related'] ?? null;
        $showRelated = $showRelatedRaw !== null ? (bool) (int) $showRelatedRaw : null;

        $metaTitleRaw = $row['meta_title'] ?? null;
        $metaTitle = ($metaTitleRaw !== null && $metaTitleRaw !== '') ? (string) $metaTitleRaw : null;

        $metaDescriptionRaw = $row['meta_description'] ?? null;
        $metaDescription = ($metaDescriptionRaw !== null && $metaDescriptionRaw !== '') ? (string) $metaDescriptionRaw : null;

        return new Entity(
            id: (int) $row['id'],
            entityTypeId: (int) $row['entity_type_id'],
            slug: $slug,
            permalink: $permalink,
            status: EntityStatus::from((string) ($row['status'] ?? EntityStatus::Draft->value)),
            publishedAt: $publishedAt,
            isDeleted: (bool) (int) $row['is_deleted'],
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
            metaTitle: $metaTitle,
            metaDescription: $metaDescription,
            scheduledAt: $scheduledAt,
            layout: $layout,
            menuOrder: (int) ($row['menu_order'] ?? 0),
            showComments: $showComments,
            showRelated: $showRelated,
        );
    }

    /** tri-state bool → nullable tinyint (NULL = follow record_page_config). */
    private static function toDbBool(?bool $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
