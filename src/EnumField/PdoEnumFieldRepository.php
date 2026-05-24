<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoEnumFieldRepository implements EnumFieldRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(int $id): ?EnumField
    {
        $row = $this->query->fetchOne(
            'SELECT id, entity_id, field_key, value FROM enum_fields WHERE id = ? AND is_deleted = 0',
            [$id],
        );

        if ($row === null) {
            return null;
        }

        return new EnumField(
            entityId: (int) $row['entity_id'],
            fieldKey: (string) $row['field_key'],
            value: (string) $row['value'],
            id: (int) $row['id'],
        );
    }

    /** @return list<EnumField> */
    public function findAll(int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, entity_id, field_key, value FROM enum_fields WHERE is_deleted = 0 ORDER BY id ASC LIMIT ? OFFSET ?',
            [$limit, $offset],
        );

        return array_map(
            static fn (array $row) => new EnumField(
                entityId: (int) $row['entity_id'],
                fieldKey: (string) $row['field_key'],
                value: (string) $row['value'],
                id: (int) $row['id'],
            ),
            $rows,
        );
    }

    public function save(EnumField $intField): int
    {
        $this->query->execute(
            'INSERT INTO enum_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$intField->entityId, $intField->fieldKey, $intField->value],
        );

        return $this->query->lastInsertId();
    }

    public function update(EnumField $intField): void
    {
        if ($intField->id === null) {
            throw new LogicException('EnumField id is required when updating.');
        }

        $affected = $this->query->execute(
            'UPDATE enum_fields SET field_key = ?, value = ? WHERE id = ? AND is_deleted = 0',
            [$intField->fieldKey, $intField->value, $intField->id],
        );

        if ($affected === 0) {
            throw new EnumFieldNotFoundException($intField->id);
        }
    }

    public function delete(int $id): void
    {
        $affected = $this->query->execute(
            'UPDATE enum_fields SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND is_deleted = 0',
            [$id],
        );

        if ($affected === 0) {
            throw new EnumFieldNotFoundException($id);
        }
    }
}
