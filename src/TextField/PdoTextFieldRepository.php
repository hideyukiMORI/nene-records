<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

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
final readonly class PdoTextFieldRepository implements TextFieldRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private readonly RequestScopedHolder $orgId,
    ) {
    }

    public function findById(int $id): ?TextField
    {
        $row = $this->query->fetchOne(
            'SELECT id, entity_id, field_key, locale, value FROM text_fields WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    /**
     * SQL fragment gating an unaliased `text_fields` query to fields whose parent
     * entity is published. Appended for anonymous callers so the open content-read
     * surface can never return draft/scheduled bodies (`?entity_id`/`findAll`). #828.
     */
    private const PUBLISHED_PARENT_GATE =
        ' AND EXISTS (SELECT 1 FROM entities e WHERE e.id = text_fields.entity_id'
        . " AND e.status = 'published' AND e.is_deleted = 0)";

    /** @return list<TextField> */
    public function findAll(int $limit, int $offset, ?string $locale = null, bool $publishedOnly = false): array
    {
        $gate = $publishedOnly ? self::PUBLISHED_PARENT_GATE : '';

        if ($locale !== null) {
            $rows = $this->query->fetchAll(
                "SELECT id, entity_id, field_key, locale, value FROM text_fields WHERE is_deleted = 0 AND locale = ? AND organization_id = ?{$gate} ORDER BY id ASC LIMIT ? OFFSET ?",
                [$locale, $this->orgId->get(), $limit, $offset],
            );
        } else {
            $rows = $this->query->fetchAll(
                "SELECT id, entity_id, field_key, locale, value FROM text_fields WHERE is_deleted = 0 AND organization_id = ?{$gate} ORDER BY id ASC LIMIT ? OFFSET ?",
                [$this->orgId->get(), $limit, $offset],
            );
        }

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    /** @return list<TextField> */
    public function findByEntityId(int $entityId, int $limit, int $offset, ?string $locale = null, bool $publishedOnly = false): array
    {
        $gate = $publishedOnly ? self::PUBLISHED_PARENT_GATE : '';

        if ($locale !== null) {
            $rows = $this->query->fetchAll(
                "SELECT id, entity_id, field_key, locale, value FROM text_fields WHERE entity_id = ? AND locale = ? AND is_deleted = 0 AND organization_id = ?{$gate} ORDER BY id ASC LIMIT ? OFFSET ?",
                [$entityId, $locale, $this->orgId->get(), $limit, $offset],
            );
        } else {
            $rows = $this->query->fetchAll(
                "SELECT id, entity_id, field_key, locale, value FROM text_fields WHERE entity_id = ? AND is_deleted = 0 AND organization_id = ?{$gate} ORDER BY id ASC LIMIT ? OFFSET ?",
                [$entityId, $this->orgId->get(), $limit, $offset],
            );
        }

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    /** @return list<TextField> */
    public function findByEntityTypeId(int $entityTypeId, int $limit, int $offset, ?string $locale = null, bool $publishedOnly = false): array
    {
        // findByEntityTypeId already joins `entities e`, so gate on the alias directly.
        $gate = $publishedOnly ? "\n                  AND e.status = 'published'" : '';

        if ($locale !== null) {
            $rows = $this->query->fetchAll(
                "SELECT tf.id, tf.entity_id, tf.field_key, tf.locale, tf.value
                FROM text_fields tf
                INNER JOIN entities e ON e.id = tf.entity_id
                WHERE tf.is_deleted = 0
                  AND e.is_deleted = 0
                  AND e.entity_type_id = ?
                  AND tf.locale = ?
                  AND tf.organization_id = ?{$gate}
                ORDER BY tf.id ASC
                LIMIT ? OFFSET ?",
                [$entityTypeId, $locale, $this->orgId->get(), $limit, $offset],
            );
        } else {
            $rows = $this->query->fetchAll(
                "SELECT tf.id, tf.entity_id, tf.field_key, tf.locale, tf.value
                FROM text_fields tf
                INNER JOIN entities e ON e.id = tf.entity_id
                WHERE tf.is_deleted = 0
                  AND e.is_deleted = 0
                  AND e.entity_type_id = ?
                  AND tf.organization_id = ?{$gate}
                ORDER BY tf.id ASC
                LIMIT ? OFFSET ?",
                [$entityTypeId, $this->orgId->get(), $limit, $offset],
            );
        }

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    /** @param list<int> $entityIds @return list<TextField> */
    public function findByEntityIds(array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($entityIds), '?'));
        $rows = $this->query->fetchAll(
            "SELECT id, entity_id, field_key, locale, value FROM text_fields WHERE entity_id IN ({$placeholders}) AND is_deleted = 0 AND organization_id = ? ORDER BY entity_id ASC, id ASC",
            [...$entityIds, $this->orgId->get()],
        );

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    public function save(TextField $textField): int
    {
        $this->query->execute(
            'INSERT INTO text_fields (organization_id, entity_id, field_key, locale, value) VALUES (?, ?, ?, ?, ?)',
            [$this->orgId->get(), $textField->entityId, $textField->fieldKey, $textField->locale, $textField->value],
        );

        return $this->query->lastInsertId();
    }

    public function update(TextField $textField): void
    {
        if ($textField->id === null) {
            throw new LogicException('TextField id is required when updating.');
        }

        $affected = $this->query->execute(
            'UPDATE text_fields SET field_key = ?, locale = ?, value = ? WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$textField->fieldKey, $textField->locale, $textField->value, $textField->id, $this->orgId->get()],
        );

        if ($affected === 0) {
            throw new TextFieldNotFoundException($textField->id);
        }
    }

    public function delete(int $id): void
    {
        $affected = $this->query->execute(
            'UPDATE text_fields SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND is_deleted = 0 AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        if ($affected === 0) {
            throw new TextFieldNotFoundException($id);
        }
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): TextField
    {
        $localeRaw = $row['locale'] ?? null;

        return new TextField(
            entityId: (int) $row['entity_id'],
            fieldKey: (string) $row['field_key'],
            value: (string) $row['value'],
            id: (int) $row['id'],
            locale: ($localeRaw !== null && $localeRaw !== '') ? (string) $localeRaw : null,
        );
    }
}
