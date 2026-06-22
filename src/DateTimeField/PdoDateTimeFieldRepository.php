<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\RequestScopedHolder;

/**
 * Per-type field-value repository (text/int/enum/bool/datetime).
 *
 * The five *Field modules are intentionally near-duplicated: NENE2 convention
 * (explicit wiring over DRY; the framework ships zero abstract classes/traits),
 * NOT debt. Do NOT extract a shared AbstractFieldRepository / trait / generic.
 * Rationale: docs/development/backend-standards.md → "Intentional per-type duplication".
 */
final readonly class PdoDateTimeFieldRepository implements DateTimeFieldRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private readonly RequestScopedHolder $orgId,
    ) {
    }

    public function findById(int $id): ?DateTimeField
    {
        $row = $this->query->fetchOne(
            'SELECT id, entity_id, field_key, value FROM datetime_fields WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        if ($row === null) {
            return null;
        }

        return new DateTimeField(
            entityId: (int) $row['entity_id'],
            fieldKey: (string) $row['field_key'],
            value: (string) $row['value'],
            id: (int) $row['id'],
        );
    }

    /** @return list<DateTimeField> */
    public function findAll(int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, entity_id, field_key, value FROM datetime_fields WHERE is_deleted = 0 AND organization_id = ? ORDER BY id ASC LIMIT ? OFFSET ?',
            [$this->orgId->get(), $limit, $offset],
        );

        return array_map(
            static fn (array $row) => new DateTimeField(
                entityId: (int) $row['entity_id'],
                fieldKey: (string) $row['field_key'],
                value: (string) $row['value'],
                id: (int) $row['id'],
            ),
            $rows,
        );
    }

    /** @return list<DateTimeField> */
    public function findByEntityId(int $entityId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, entity_id, field_key, value FROM datetime_fields WHERE entity_id = ? AND is_deleted = 0 AND organization_id = ? ORDER BY id ASC LIMIT ? OFFSET ?',
            [$entityId, $this->orgId->get(), $limit, $offset],
        );

        return array_map(
            static fn (array $row) => new DateTimeField(
                entityId: (int) $row['entity_id'],
                fieldKey: (string) $row['field_key'],
                value: (string) $row['value'],
                id: (int) $row['id'],
            ),
            $rows,
        );
    }

    public function save(DateTimeField $dateTimeField): int
    {
        $this->query->execute(
            'INSERT INTO datetime_fields (organization_id, entity_id, field_key, value) VALUES (?, ?, ?, ?)',
            [$this->orgId->get(), $dateTimeField->entityId, $dateTimeField->fieldKey, $dateTimeField->value],
        );

        return $this->query->lastInsertId();
    }

    public function update(DateTimeField $dateTimeField): void
    {
        if ($dateTimeField->id === null) {
            throw new LogicException('DateTimeField id is required when updating.');
        }

        $affected = $this->query->execute(
            'UPDATE datetime_fields SET field_key = ?, value = ? WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$dateTimeField->fieldKey, $dateTimeField->value, $dateTimeField->id, $this->orgId->get()],
        );

        if ($affected === 0) {
            throw new DateTimeFieldNotFoundException($dateTimeField->id);
        }
    }

    public function delete(int $id): void
    {
        $affected = $this->query->execute(
            'UPDATE datetime_fields SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        if ($affected === 0) {
            throw new DateTimeFieldNotFoundException($id);
        }
    }
}
