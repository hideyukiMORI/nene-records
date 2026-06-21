<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoBlocksFieldRepository implements BlocksFieldRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private readonly RequestScopedHolder $orgId,
    ) {
    }

    public function findById(int $id): ?BlocksField
    {
        $row = $this->query->fetchOne(
            'SELECT id, entity_id, field_key, locale, value FROM blocks_fields WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    /** @return list<BlocksField> */
    public function findAll(int $limit, int $offset, ?string $locale = null): array
    {
        if ($locale !== null) {
            $rows = $this->query->fetchAll(
                'SELECT id, entity_id, field_key, locale, value FROM blocks_fields WHERE is_deleted = 0 AND locale = ? AND organization_id = ? ORDER BY id ASC LIMIT ? OFFSET ?',
                [$locale, $this->orgId->get(), $limit, $offset],
            );
        } else {
            $rows = $this->query->fetchAll(
                'SELECT id, entity_id, field_key, locale, value FROM blocks_fields WHERE is_deleted = 0 AND organization_id = ? ORDER BY id ASC LIMIT ? OFFSET ?',
                [$this->orgId->get(), $limit, $offset],
            );
        }

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    /** @return list<BlocksField> */
    public function findByEntityId(int $entityId, int $limit, int $offset, ?string $locale = null): array
    {
        if ($locale !== null) {
            $rows = $this->query->fetchAll(
                'SELECT id, entity_id, field_key, locale, value FROM blocks_fields WHERE entity_id = ? AND locale = ? AND is_deleted = 0 AND organization_id = ? ORDER BY id ASC LIMIT ? OFFSET ?',
                [$entityId, $locale, $this->orgId->get(), $limit, $offset],
            );
        } else {
            $rows = $this->query->fetchAll(
                'SELECT id, entity_id, field_key, locale, value FROM blocks_fields WHERE entity_id = ? AND is_deleted = 0 AND organization_id = ? ORDER BY id ASC LIMIT ? OFFSET ?',
                [$entityId, $this->orgId->get(), $limit, $offset],
            );
        }

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    /** @return list<BlocksField> */
    public function findByEntityTypeId(int $entityTypeId, int $limit, int $offset, ?string $locale = null): array
    {
        if ($locale !== null) {
            $rows = $this->query->fetchAll(
                <<<'SQL'
                SELECT bf.id, bf.entity_id, bf.field_key, bf.locale, bf.value
                FROM blocks_fields bf
                INNER JOIN entities e ON e.id = bf.entity_id
                WHERE bf.is_deleted = 0
                  AND e.is_deleted = 0
                  AND e.entity_type_id = ?
                  AND bf.locale = ?
                  AND bf.organization_id = ?
                ORDER BY bf.id ASC
                LIMIT ? OFFSET ?
                SQL,
                [$entityTypeId, $locale, $this->orgId->get(), $limit, $offset],
            );
        } else {
            $rows = $this->query->fetchAll(
                <<<'SQL'
                SELECT bf.id, bf.entity_id, bf.field_key, bf.locale, bf.value
                FROM blocks_fields bf
                INNER JOIN entities e ON e.id = bf.entity_id
                WHERE bf.is_deleted = 0
                  AND e.is_deleted = 0
                  AND e.entity_type_id = ?
                  AND bf.organization_id = ?
                ORDER BY bf.id ASC
                LIMIT ? OFFSET ?
                SQL,
                [$entityTypeId, $this->orgId->get(), $limit, $offset],
            );
        }

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    /** @param list<int> $entityIds @return list<BlocksField> */
    public function findByEntityIds(array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($entityIds), '?'));
        $rows = $this->query->fetchAll(
            "SELECT id, entity_id, field_key, locale, value FROM blocks_fields WHERE entity_id IN ({$placeholders}) AND is_deleted = 0 AND organization_id = ? ORDER BY entity_id ASC, id ASC",
            [...$entityIds, $this->orgId->get()],
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function save(BlocksField $blocksField): int
    {
        $this->query->execute(
            'INSERT INTO blocks_fields (organization_id, entity_id, field_key, locale, value) VALUES (?, ?, ?, ?, ?)',
            [$this->orgId->get(), $blocksField->entityId, $blocksField->fieldKey, $blocksField->locale, $blocksField->value],
        );

        return $this->query->lastInsertId();
    }

    public function update(BlocksField $blocksField): void
    {
        if ($blocksField->id === null) {
            throw new LogicException('BlocksField id is required when updating.');
        }

        $affected = $this->query->execute(
            'UPDATE blocks_fields SET field_key = ?, locale = ?, value = ? WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$blocksField->fieldKey, $blocksField->locale, $blocksField->value, $blocksField->id, $this->orgId->get()],
        );

        if ($affected === 0) {
            throw new BlocksFieldNotFoundException($blocksField->id);
        }
    }

    public function delete(int $id): void
    {
        $affected = $this->query->execute(
            'UPDATE blocks_fields SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        if ($affected === 0) {
            throw new BlocksFieldNotFoundException($id);
        }
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): BlocksField
    {
        $localeRaw = $row['locale'] ?? null;

        return new BlocksField(
            entityId: (int) $row['entity_id'],
            fieldKey: (string) $row['field_key'],
            value: (string) $row['value'],
            id: (int) $row['id'],
            locale: ($localeRaw !== null && $localeRaw !== '') ? (string) $localeRaw : null,
        );
    }
}
