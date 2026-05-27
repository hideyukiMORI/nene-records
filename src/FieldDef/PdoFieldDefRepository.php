<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use DateTimeImmutable;
use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoFieldDefRepository implements FieldDefRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private RequestScopedHolder $orgId,
    ) {
    }

    public function findById(int $id): ?FieldDef
    {
        $row = $this->query->fetchOne(
            <<<'SQL'
                SELECT id, entity_type_id, field_key, data_type, target_entity_type_id, cardinality, is_deleted, deleted_at
                FROM field_defs
                WHERE id = ? AND organization_id = ? AND is_deleted = 0
                SQL,
            [$id, $this->orgId->get()],
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
                SELECT id, entity_type_id, field_key, data_type, target_entity_type_id, cardinality, is_deleted, deleted_at
                FROM field_defs
                WHERE entity_type_id = ? AND field_key = ? AND organization_id = ? AND is_deleted = 0
                SQL,
            [$entityTypeId, $fieldKey, $this->orgId->get()],
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
                    SELECT id, entity_type_id, field_key, data_type, target_entity_type_id, cardinality, is_deleted, deleted_at
                    FROM field_defs
                    WHERE is_deleted = 0 AND entity_type_id = ? AND organization_id = ?
                    ORDER BY id ASC
                    LIMIT ? OFFSET ?
                    SQL,
                [$entityTypeId, $this->orgId->get(), $limit, $offset],
            );
        } else {
            $rows = $this->query->fetchAll(
                <<<'SQL'
                    SELECT id, entity_type_id, field_key, data_type, target_entity_type_id, cardinality, is_deleted, deleted_at
                    FROM field_defs
                    WHERE is_deleted = 0 AND organization_id = ?
                    ORDER BY id ASC
                    LIMIT ? OFFSET ?
                    SQL,
                [$this->orgId->get(), $limit, $offset],
            );
        }

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function save(FieldDef $fieldDef): int
    {
        $this->query->execute(
            <<<'SQL'
                INSERT INTO field_defs (organization_id, entity_type_id, field_key, data_type, target_entity_type_id, cardinality)
                VALUES (?, ?, ?, ?, ?, ?)
                SQL,
            [
                $this->orgId->get(),
                $fieldDef->entityTypeId,
                $fieldDef->fieldKey,
                $fieldDef->dataType,
                $fieldDef->targetEntityTypeId,
                $fieldDef->cardinality,
            ],
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
                SET entity_type_id = ?, field_key = ?, data_type = ?, target_entity_type_id = ?, cardinality = ?
                WHERE id = ? AND organization_id = ? AND is_deleted = 0
                SQL,
            [
                $fieldDef->entityTypeId,
                $fieldDef->fieldKey,
                $fieldDef->dataType,
                $fieldDef->targetEntityTypeId,
                $fieldDef->cardinality,
                $id,
                $this->orgId->get(),
            ],
        );
    }

    public function softDelete(int $id): void
    {
        $this->query->execute(
            <<<'SQL'
                UPDATE field_defs
                SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP
                WHERE id = ? AND organization_id = ? AND is_deleted = 0
                SQL,
            [$id, $this->orgId->get()],
        );
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): FieldDef
    {
        $deletedRaw = $row['deleted_at'] ?? null;
        $deletedAt = ($deletedRaw !== null && $deletedRaw !== '') ? new DateTimeImmutable((string) $deletedRaw) : null;
        $targetEntityTypeIdRaw = $row['target_entity_type_id'] ?? null;

        return new FieldDef(
            entityTypeId: (int) $row['entity_type_id'],
            fieldKey: (string) $row['field_key'],
            dataType: (string) $row['data_type'],
            id: (int) $row['id'],
            isDeleted: (bool) (int) $row['is_deleted'],
            deletedAt: $deletedAt,
            targetEntityTypeId: $targetEntityTypeIdRaw === null ? null : (int) $targetEntityTypeIdRaw,
            cardinality: ($row['cardinality'] ?? null) !== null && $row['cardinality'] !== ''
                ? (string) $row['cardinality']
                : null,
        );
    }
}
