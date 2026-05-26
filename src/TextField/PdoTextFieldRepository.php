<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoTextFieldRepository implements TextFieldRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(int $id): ?TextField
    {
        $row = $this->query->fetchOne(
            'SELECT id, entity_id, field_key, value FROM text_fields WHERE id = ? AND is_deleted = 0',
            [$id],
        );

        if ($row === null) {
            return null;
        }

        return new TextField(
            entityId: (int) $row['entity_id'],
            fieldKey: (string) $row['field_key'],
            value: (string) $row['value'],
            id: (int) $row['id'],
        );
    }

    /** @return list<TextField> */
    public function findAll(int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, entity_id, field_key, value FROM text_fields WHERE is_deleted = 0 ORDER BY id ASC LIMIT ? OFFSET ?',
            [$limit, $offset],
        );

        return array_map(
            static fn (array $row) => new TextField(
                entityId: (int) $row['entity_id'],
                fieldKey: (string) $row['field_key'],
                value: (string) $row['value'],
                id: (int) $row['id'],
            ),
            $rows,
        );
    }

    /** @return list<TextField> */
    public function findByEntityId(int $entityId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, entity_id, field_key, value FROM text_fields WHERE entity_id = ? AND is_deleted = 0 ORDER BY id ASC LIMIT ? OFFSET ?',
            [$entityId, $limit, $offset],
        );

        return array_map(
            static fn (array $row) => new TextField(
                entityId: (int) $row['entity_id'],
                fieldKey: (string) $row['field_key'],
                value: (string) $row['value'],
                id: (int) $row['id'],
            ),
            $rows,
        );
    }

    /** @return list<TextField> */
    public function findByEntityTypeId(int $entityTypeId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            <<<'SQL'
            SELECT tf.id, tf.entity_id, tf.field_key, tf.value
            FROM text_fields tf
            INNER JOIN entities e ON e.id = tf.entity_id
            WHERE tf.is_deleted = 0
              AND e.is_deleted = 0
              AND e.entity_type_id = ?
            ORDER BY tf.id ASC
            LIMIT ? OFFSET ?
            SQL,
            [$entityTypeId, $limit, $offset],
        );

        return array_map(
            static fn (array $row) => new TextField(
                entityId: (int) $row['entity_id'],
                fieldKey: (string) $row['field_key'],
                value: (string) $row['value'],
                id: (int) $row['id'],
            ),
            $rows,
        );
    }

    /** @param list<int> $entityIds @return list<TextField> */
    public function findByEntityIds(array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($entityIds), '?'));
        $rows = $this->query->fetchAll(
            "SELECT id, entity_id, field_key, value FROM text_fields WHERE entity_id IN ({$placeholders}) AND is_deleted = 0 ORDER BY entity_id ASC, id ASC",
            $entityIds,
        );

        return array_map(
            static fn (array $row) => new TextField(
                entityId: (int) $row['entity_id'],
                fieldKey: (string) $row['field_key'],
                value: (string) $row['value'],
                id: (int) $row['id'],
            ),
            $rows,
        );
    }

    public function save(TextField $textField): int
    {
        $this->query->execute(
            'INSERT INTO text_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$textField->entityId, $textField->fieldKey, $textField->value],
        );

        return $this->query->lastInsertId();
    }

    public function update(TextField $textField): void
    {
        if ($textField->id === null) {
            throw new LogicException('TextField id is required when updating.');
        }

        $affected = $this->query->execute(
            'UPDATE text_fields SET field_key = ?, value = ? WHERE id = ? AND is_deleted = 0',
            [$textField->fieldKey, $textField->value, $textField->id],
        );

        if ($affected === 0) {
            throw new TextFieldNotFoundException($textField->id);
        }
    }

    public function delete(int $id): void
    {
        $affected = $this->query->execute(
            'UPDATE text_fields SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND is_deleted = 0',
            [$id],
        );

        if ($affected === 0) {
            throw new TextFieldNotFoundException($id);
        }
    }
}
