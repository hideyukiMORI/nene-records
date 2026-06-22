<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

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

    public function save(BoolField $boolField): int
    {
        $this->query->execute(
            'INSERT INTO bool_fields (organization_id, entity_id, field_key, value) VALUES (?, ?, ?, ?)',
            [$this->orgId->get(), $boolField->entityId, $boolField->fieldKey, $boolField->value],
        );

        return $this->query->lastInsertId();
    }

    public function update(BoolField $boolField): void
    {
        if ($boolField->id === null) {
            throw new LogicException('BoolField id is required when updating.');
        }

        $affected = $this->query->execute(
            'UPDATE bool_fields SET field_key = ?, value = ? WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$boolField->fieldKey, $boolField->value, $boolField->id, $this->orgId->get()],
        );

        if ($affected === 0) {
            throw new BoolFieldNotFoundException($boolField->id);
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
