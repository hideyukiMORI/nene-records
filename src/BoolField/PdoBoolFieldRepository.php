<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoBoolFieldRepository implements BoolFieldRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private readonly RequestScopedHolder $orgId,
    ) {
    }

    public function findById(int $id): ?BoolField
    {
        $row = $this->query->fetchOne(
            'SELECT id, entity_id, field_key, value FROM bool_fields WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        if ($row === null) {
            return null;
        }

        return new BoolField(
            entityId: (int) $row['entity_id'],
            fieldKey: (string) $row['field_key'],
            value: (bool) (int) $row['value'],
            id: (int) $row['id'],
        );
    }

    /** @return list<BoolField> */
    public function findAll(int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, entity_id, field_key, value FROM bool_fields WHERE is_deleted = 0 AND organization_id = ? ORDER BY id ASC LIMIT ? OFFSET ?',
            [$this->orgId->get(), $limit, $offset],
        );

        return array_map(
            static fn (array $row) => new BoolField(
                entityId: (int) $row['entity_id'],
                fieldKey: (string) $row['field_key'],
                value: (bool) (int) $row['value'],
                id: (int) $row['id'],
            ),
            $rows,
        );
    }

    /** @return list<BoolField> */
    public function findByEntityId(int $entityId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, entity_id, field_key, value FROM bool_fields WHERE entity_id = ? AND is_deleted = 0 AND organization_id = ? ORDER BY id ASC LIMIT ? OFFSET ?',
            [$entityId, $this->orgId->get(), $limit, $offset],
        );

        return array_map(
            static fn (array $row) => new BoolField(
                entityId: (int) $row['entity_id'],
                fieldKey: (string) $row['field_key'],
                value: (bool) (int) $row['value'],
                id: (int) $row['id'],
            ),
            $rows,
        );
    }

    public function save(BoolField $intField): int
    {
        $this->query->execute(
            'INSERT INTO bool_fields (organization_id, entity_id, field_key, value) VALUES (?, ?, ?, ?)',
            [$this->orgId->get(), $intField->entityId, $intField->fieldKey, $intField->value],
        );

        return $this->query->lastInsertId();
    }

    public function update(BoolField $intField): void
    {
        if ($intField->id === null) {
            throw new LogicException('BoolField id is required when updating.');
        }

        $affected = $this->query->execute(
            'UPDATE bool_fields SET field_key = ?, value = ? WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$intField->fieldKey, $intField->value, $intField->id, $this->orgId->get()],
        );

        if ($affected === 0) {
            throw new BoolFieldNotFoundException($intField->id);
        }
    }

    public function delete(int $id): void
    {
        $affected = $this->query->execute(
            'UPDATE bool_fields SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        if ($affected === 0) {
            throw new BoolFieldNotFoundException($id);
        }
    }
}
