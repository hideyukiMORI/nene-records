<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use DateTimeImmutable;
use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoFieldDefRepository implements FieldDefRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(int $id): ?FieldDef
    {
        $row = $this->query->fetchOne(
            <<<'SQL'
                SELECT id, entity_type_id, field_key, data_type, is_deleted, deleted_at
                FROM field_defs
                WHERE id = ? AND is_deleted = 0
                SQL,
            [$id],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function findByEntityTypeIdAndFieldKey(int $entityTypeId, string $fieldKey): ?FieldDef
    {
        $row = $this->query->fetchOne(
            <<<'SQL'
                SELECT id, entity_type_id, field_key, data_type, is_deleted, deleted_at
                FROM field_defs
                WHERE entity_type_id = ? AND field_key = ? AND is_deleted = 0
                SQL,
            [$entityTypeId, $fieldKey],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    /** @return list<FieldDef> */
    public function findAll(?int $entityTypeId, int $limit, int $offset): array
    {
        if ($entityTypeId !== null) {
            $rows = $this->query->fetchAll(
                <<<'SQL'
                    SELECT id, entity_type_id, field_key, data_type, is_deleted, deleted_at
                    FROM field_defs
                    WHERE is_deleted = 0 AND entity_type_id = ?
                    ORDER BY id ASC
                    LIMIT ? OFFSET ?
                    SQL,
                [$entityTypeId, $limit, $offset],
            );
        } else {
            $rows = $this->query->fetchAll(
                <<<'SQL'
                    SELECT id, entity_type_id, field_key, data_type, is_deleted, deleted_at
                    FROM field_defs
                    WHERE is_deleted = 0
                    ORDER BY id ASC
                    LIMIT ? OFFSET ?
                    SQL,
                [$limit, $offset],
            );
        }

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function save(FieldDef $fieldDef): int
    {
        $this->query->execute(
            'INSERT INTO field_defs (entity_type_id, field_key, data_type) VALUES (?, ?, ?)',
            [$fieldDef->entityTypeId, $fieldDef->fieldKey, $fieldDef->dataType],
        );

        return $this->query->lastInsertId();
    }

    public function update(FieldDef $fieldDef): void
    {
        $id = $fieldDef->id;

        if ($id === null) {
            throw new LogicException('FieldDef id must be set before update.');
        }

        $this->query->execute(
            <<<'SQL'
                UPDATE field_defs
                SET entity_type_id = ?, field_key = ?, data_type = ?
                WHERE id = ? AND is_deleted = 0
                SQL,
            [$fieldDef->entityTypeId, $fieldDef->fieldKey, $fieldDef->dataType, $id],
        );
    }

    public function softDelete(int $id): void
    {
        $this->query->execute(
            <<<'SQL'
                UPDATE field_defs
                SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP
                WHERE id = ? AND is_deleted = 0
                SQL,
            [$id],
        );
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): FieldDef
    {
        $deletedRaw = $row['deleted_at'] ?? null;
        $deletedAt = ($deletedRaw !== null && $deletedRaw !== '') ? new DateTimeImmutable((string) $deletedRaw) : null;

        return new FieldDef(
            entityTypeId: (int) $row['entity_type_id'],
            fieldKey: (string) $row['field_key'],
            dataType: (string) $row['data_type'],
            id: (int) $row['id'],
            isDeleted: (bool) (int) $row['is_deleted'],
            deletedAt: $deletedAt,
        );
    }
}
