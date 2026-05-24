<?php

declare(strict_types=1);

namespace NeNeRecords\EntityArchive;

use DateTimeImmutable;
use Nene2\Database\DatabaseQueryExecutorInterface;
use NeNeRecords\EntityType\EntityType;

final readonly class PdoEntityArchiveRepository implements EntityArchiveRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function archiveAndPurgeSoftDeleted(EntityType $entityType): void
    {
        $rows = $this->query->fetchAll(
            'SELECT id, slug, status, deleted_at FROM entities WHERE entity_type_id = ? AND is_deleted = 1',
            [$entityType->id],
        );

        if ($rows === []) {
            return;
        }

        $entityIds = array_column($rows, 'id');
        $placeholders = implode(',', array_fill(0, count($entityIds), '?'));

        $textFields = $this->query->fetchAll(
            "SELECT entity_id, field_key, value FROM text_fields WHERE entity_id IN ({$placeholders})",
            $entityIds,
        );
        $intFields = $this->query->fetchAll(
            "SELECT entity_id, field_key, value FROM int_fields WHERE entity_id IN ({$placeholders})",
            $entityIds,
        );
        $boolFields = $this->query->fetchAll(
            "SELECT entity_id, field_key, value FROM bool_fields WHERE entity_id IN ({$placeholders})",
            $entityIds,
        );
        $enumFields = $this->query->fetchAll(
            "SELECT entity_id, field_key, value FROM enum_fields WHERE entity_id IN ({$placeholders})",
            $entityIds,
        );
        $datetimeFields = $this->query->fetchAll(
            "SELECT entity_id, field_key, value FROM datetime_fields WHERE entity_id IN ({$placeholders})",
            $entityIds,
        );
        $tags = $this->query->fetchAll(
            "SELECT et.entity_id, t.slug FROM entity_tags et JOIN tags t ON t.id = et.tag_id WHERE et.entity_id IN ({$placeholders})",
            $entityIds,
        );
        $relations = $this->query->fetchAll(
            "SELECT source_entity_id, target_entity_id, field_key FROM entity_relations WHERE source_entity_id IN ({$placeholders})",
            $entityIds,
        );

        $fieldsByEntity = $this->groupBy($textFields, 'entity_id');
        $intByEntity = $this->groupBy($intFields, 'entity_id');
        $boolByEntity = $this->groupBy($boolFields, 'entity_id');
        $enumByEntity = $this->groupBy($enumFields, 'entity_id');
        $datetimeByEntity = $this->groupBy($datetimeFields, 'entity_id');
        $tagsByEntity = $this->groupBy($tags, 'entity_id');
        $relationsByEntity = $this->groupBy($relations, 'source_entity_id');

        $now = new DateTimeImmutable();
        $archivedAt = $now->format('Y-m-d H:i:s');

        foreach ($rows as $row) {
            $entityId = (int) $row['id'];
            $snapshot = [
                'text_fields' => array_map(
                    static fn (array $f) => ['field_key' => $f['field_key'], 'value' => $f['value']],
                    $fieldsByEntity[$entityId] ?? [],
                ),
                'int_fields' => array_map(
                    static fn (array $f) => ['field_key' => $f['field_key'], 'value' => (int) $f['value']],
                    $intByEntity[$entityId] ?? [],
                ),
                'bool_fields' => array_map(
                    static fn (array $f) => ['field_key' => $f['field_key'], 'value' => (bool) $f['value']],
                    $boolByEntity[$entityId] ?? [],
                ),
                'enum_fields' => array_map(
                    static fn (array $f) => ['field_key' => $f['field_key'], 'value' => $f['value']],
                    $enumByEntity[$entityId] ?? [],
                ),
                'datetime_fields' => array_map(
                    static fn (array $f) => ['field_key' => $f['field_key'], 'value' => $f['value']],
                    $datetimeByEntity[$entityId] ?? [],
                ),
                'tags' => array_column($tagsByEntity[$entityId] ?? [], 'slug'),
                'relations' => array_map(
                    static fn (array $r) => [
                        'field_key' => $r['field_key'],
                        'target_entity_id' => (int) $r['target_entity_id'],
                    ],
                    $relationsByEntity[$entityId] ?? [],
                ),
            ];

            $this->query->execute(
                <<<SQL
                    INSERT INTO entity_archive
                        (original_entity_id, entity_type_id, entity_type_slug, entity_type_name,
                         entity_slug, entity_status, deleted_at, archived_at, archived_reason, snapshot)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'entity_type_deleted', ?)
                    SQL,
                [
                    $entityId,
                    $entityType->id,
                    $entityType->slug,
                    $entityType->name,
                    $row['slug'],
                    $row['status'],
                    $row['deleted_at'],
                    $archivedAt,
                    json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                ],
            );
        }

        $this->query->execute(
            "DELETE FROM text_fields WHERE entity_id IN ({$placeholders})",
            $entityIds,
        );
        $this->query->execute(
            "DELETE FROM int_fields WHERE entity_id IN ({$placeholders})",
            $entityIds,
        );
        $this->query->execute(
            "DELETE FROM bool_fields WHERE entity_id IN ({$placeholders})",
            $entityIds,
        );
        $this->query->execute(
            "DELETE FROM enum_fields WHERE entity_id IN ({$placeholders})",
            $entityIds,
        );
        $this->query->execute(
            "DELETE FROM datetime_fields WHERE entity_id IN ({$placeholders})",
            $entityIds,
        );
        $this->query->execute(
            'DELETE FROM entities WHERE entity_type_id = ? AND is_deleted = 1',
            [$entityType->id],
        );
    }

    /** @return list<ArchivedEntity> */
    public function findByEntityTypeId(int $entityTypeId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            <<<SQL
                SELECT id, original_entity_id, entity_type_id, entity_type_slug, entity_type_name,
                       entity_slug, entity_status, deleted_at, archived_at, archived_reason, snapshot
                FROM entity_archive
                WHERE entity_type_id = ?
                ORDER BY archived_at DESC
                LIMIT ? OFFSET ?
                SQL,
            [$entityTypeId, $limit, $offset],
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function countByEntityTypeId(int $entityTypeId): int
    {
        $row = $this->query->fetchOne(
            'SELECT COUNT(*) AS total FROM entity_archive WHERE entity_type_id = ?',
            [$entityTypeId],
        );

        return (int) ($row['total'] ?? 0);
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): ArchivedEntity
    {
        return new ArchivedEntity(
            originalEntityId: (int) $row['original_entity_id'],
            entityTypeId: (int) $row['entity_type_id'],
            entityTypeSlug: $row['entity_type_slug'],
            entityTypeName: $row['entity_type_name'],
            entitySlug: $row['entity_slug'],
            entityStatus: $row['entity_status'],
            deletedAt: $row['deleted_at'] !== null ? new DateTimeImmutable($row['deleted_at']) : null,
            archivedAt: new DateTimeImmutable($row['archived_at']),
            archivedReason: $row['archived_reason'],
            snapshot: (array) json_decode($row['snapshot'], true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<int, list<array<string, mixed>>>
     */
    private function groupBy(array $rows, string $key): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row[$key]][] = $row;
        }

        return $result;
    }
}
