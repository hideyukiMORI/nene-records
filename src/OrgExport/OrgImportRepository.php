<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Inserts an exported org payload into the target organization.
 *
 * All primary-key IDs are remapped to new auto-increment values so the import
 * can safely coexist with existing data. Cross-table references are updated
 * accordingly before insertion.
 */
final readonly class OrgImportRepository
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    /**
     * Imports exported data into the given organization.
     *
     * @param  array<string, mixed> $payload  The decoded JSON export payload.
     * @return array<string, int>             Row counts per table.
     */
    public function import(int $targetOrgId, array $payload): array
    {
        $counts = [];

        // ── entity_types ───────────────────────────────────────────────────
        /** @var array<int, int> $entityTypeMap  old_id → new_id */
        $entityTypeMap = [];
        foreach ((array) ($payload['entity_types'] ?? []) as $row) {
            $oldId = (int) $row['id'];
            $newId = $this->query->insert(
                'INSERT INTO entity_types
                    (organization_id, name, slug, is_pinned, labels, permalink_pattern, previous_permalink_pattern)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    (string) $row['name'],
                    (string) $row['slug'],
                    (int) $row['is_pinned'],
                    isset($row['labels']) ? (string) $row['labels'] : null,
                    isset($row['permalink_pattern']) ? (string) $row['permalink_pattern'] : null,
                    isset($row['previous_permalink_pattern']) ? (string) $row['previous_permalink_pattern'] : null,
                ],
            );
            $entityTypeMap[$oldId] = $newId;
        }
        $counts['entity_types'] = count($entityTypeMap);

        // ── field_defs ─────────────────────────────────────────────────────
        /** @var array<int, int> $fieldDefMap  old_id → new_id */
        $fieldDefMap = [];
        foreach ((array) ($payload['field_defs'] ?? []) as $row) {
            $oldId           = (int) $row['id'];
            $newEntityTypeId = $entityTypeMap[(int) $row['entity_type_id']] ?? null;
            if ($newEntityTypeId === null) {
                continue;
            }
            $newTargetEntityTypeId = isset($row['target_entity_type_id'])
                ? ($entityTypeMap[(int) $row['target_entity_type_id']] ?? null)
                : null;
            $newId = $this->query->insert(
                'INSERT INTO field_defs
                    (organization_id, entity_type_id, field_key, data_type, target_entity_type_id, cardinality, is_deleted, deleted_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    $newEntityTypeId,
                    (string) $row['field_key'],
                    (string) $row['data_type'],
                    $newTargetEntityTypeId,
                    isset($row['cardinality']) ? (string) $row['cardinality'] : null,
                    (int) $row['is_deleted'],
                    isset($row['deleted_at']) ? (string) $row['deleted_at'] : null,
                ],
            );
            $fieldDefMap[$oldId] = $newId;
        }
        $counts['field_defs'] = count($fieldDefMap);

        // ── entities ───────────────────────────────────────────────────────
        /** @var array<int, int> $entityMap  old_id → new_id */
        $entityMap = [];
        foreach ((array) ($payload['entities'] ?? []) as $row) {
            $oldId           = (int) $row['id'];
            $newEntityTypeId = $entityTypeMap[(int) $row['entity_type_id']] ?? null;
            if ($newEntityTypeId === null) {
                continue;
            }
            $newId = $this->query->insert(
                'INSERT INTO entities
                    (organization_id, entity_type_id, slug, status, published_at, scheduled_at,
                     meta_title, meta_description, is_deleted, created_at, updated_at, deleted_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    $newEntityTypeId,
                    isset($row['slug']) ? (string) $row['slug'] : null,
                    (string) ($row['status'] ?? 'draft'),
                    isset($row['published_at']) ? (string) $row['published_at'] : null,
                    isset($row['scheduled_at']) ? (string) $row['scheduled_at'] : null,
                    isset($row['meta_title']) ? (string) $row['meta_title'] : null,
                    isset($row['meta_description']) ? (string) $row['meta_description'] : null,
                    (int) $row['is_deleted'],
                    isset($row['created_at']) ? (string) $row['created_at'] : null,
                    isset($row['updated_at']) ? (string) $row['updated_at'] : null,
                    isset($row['deleted_at']) ? (string) $row['deleted_at'] : null,
                ],
            );
            $entityMap[$oldId] = $newId;
        }
        $counts['entities'] = count($entityMap);

        // ── text_fields ────────────────────────────────────────────────────
        $counts['text_fields'] = $this->importFieldValues(
            'text_fields',
            (array) ($payload['text_fields'] ?? []),
            $targetOrgId,
            $entityMap,
            'value',
        );

        // ── int_fields ─────────────────────────────────────────────────────
        $counts['int_fields'] = $this->importFieldValues(
            'int_fields',
            (array) ($payload['int_fields'] ?? []),
            $targetOrgId,
            $entityMap,
            'value',
        );

        // ── enum_fields ────────────────────────────────────────────────────
        $counts['enum_fields'] = $this->importFieldValues(
            'enum_fields',
            (array) ($payload['enum_fields'] ?? []),
            $targetOrgId,
            $entityMap,
            'value',
        );

        // ── bool_fields ────────────────────────────────────────────────────
        $counts['bool_fields'] = $this->importFieldValues(
            'bool_fields',
            (array) ($payload['bool_fields'] ?? []),
            $targetOrgId,
            $entityMap,
            'value',
        );

        // ── datetime_fields ────────────────────────────────────────────────
        $counts['datetime_fields'] = $this->importFieldValues(
            'datetime_fields',
            (array) ($payload['datetime_fields'] ?? []),
            $targetOrgId,
            $entityMap,
            'value',
        );

        // ── tags ───────────────────────────────────────────────────────────
        /** @var array<int, int> $tagMap  old_id → new_id */
        $tagMap = [];
        foreach ((array) ($payload['tags'] ?? []) as $row) {
            $oldId = (int) $row['id'];
            // tags.slug is globally unique; reuse existing tag if slug already exists.
            $existing = $this->query->fetchOne(
                'SELECT id FROM tags WHERE slug = ?',
                [(string) $row['slug']],
            );
            if ($existing !== null) {
                $tagMap[$oldId] = (int) $existing['id'];
            } else {
                $newId = $this->query->insert(
                    'INSERT INTO tags (organization_id, slug, name) VALUES (?, ?, ?)',
                    [$targetOrgId, (string) $row['slug'], (string) $row['name']],
                );
                $tagMap[$oldId] = $newId;
            }
        }
        $counts['tags'] = count($tagMap);

        // ── entity_tags ────────────────────────────────────────────────────
        $entityTagCount = 0;
        foreach ((array) ($payload['entity_tags'] ?? []) as $row) {
            $newEntityId = $entityMap[(int) $row['entity_id']] ?? null;
            $newTagId    = $tagMap[(int) $row['tag_id']] ?? null;
            if ($newEntityId === null || $newTagId === null) {
                continue;
            }
            $this->query->execute(
                'INSERT IGNORE INTO entity_tags (entity_id, tag_id) VALUES (?, ?)',
                [$newEntityId, $newTagId],
            );
            $entityTagCount++;
        }
        $counts['entity_tags'] = $entityTagCount;

        // ── navigation_items ───────────────────────────────────────────────
        $navCount = 0;
        foreach ((array) ($payload['navigation_items'] ?? []) as $row) {
            $this->query->insert(
                'INSERT INTO navigation_items (organization_id, label, url, display_order, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    (string) $row['label'],
                    (string) $row['url'],
                    (int) $row['display_order'],
                    (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
                    (string) ($row['updated_at'] ?? date('Y-m-d H:i:s')),
                ],
            );
            $navCount++;
        }
        $counts['navigation_items'] = $navCount;

        // ── setting_defs ───────────────────────────────────────────────────
        $settingDefCount = 0;
        foreach ((array) ($payload['setting_defs'] ?? []) as $row) {
            $this->query->insert(
                'INSERT INTO setting_defs (organization_id, setting_key, data_type, default_value, is_public, label, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    (string) $row['setting_key'],
                    (string) $row['data_type'],
                    isset($row['default_value']) ? (string) $row['default_value'] : null,
                    (int) $row['is_public'],
                    (string) $row['label'],
                    (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
                    (string) ($row['updated_at'] ?? date('Y-m-d H:i:s')),
                ],
            );
            $settingDefCount++;
        }
        $counts['setting_defs'] = $settingDefCount;

        // ── setting_values ─────────────────────────────────────────────────
        $settingValueCount = 0;
        foreach ((array) ($payload['setting_values'] ?? []) as $row) {
            $this->query->insert(
                'INSERT INTO setting_values (organization_id, setting_key, value, is_deleted, deleted_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    (string) $row['setting_key'],
                    isset($row['value']) ? (string) $row['value'] : null,
                    (int) $row['is_deleted'],
                    isset($row['deleted_at']) ? (string) $row['deleted_at'] : null,
                    (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
                    (string) ($row['updated_at'] ?? date('Y-m-d H:i:s')),
                ],
            );
            $settingValueCount++;
        }
        $counts['setting_values'] = $settingValueCount;

        // ── media ──────────────────────────────────────────────────────────
        $mediaCount = 0;
        foreach ((array) ($payload['media'] ?? []) as $row) {
            $this->query->insert(
                'INSERT INTO media (organization_id, original_name, stored_name, mime_type, size, url, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    (string) $row['original_name'],
                    (string) $row['stored_name'],
                    (string) $row['mime_type'],
                    (int) $row['size'],
                    (string) $row['url'],
                    (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
                ],
            );
            $mediaCount++;
        }
        $counts['media'] = $mediaCount;

        return $counts;
    }

    /**
     * Inserts field value rows (text/int/enum/bool/datetime) for the given table.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, int>                  $entityMap  old entity_id → new entity_id
     */
    private function importFieldValues(
        string $table,
        array $rows,
        int $targetOrgId,
        array $entityMap,
        string $valueColumn,
    ): int {
        $count = 0;
        foreach ($rows as $row) {
            $newEntityId = $entityMap[(int) $row['entity_id']] ?? null;
            if ($newEntityId === null) {
                continue;
            }
            $locale = isset($row['locale']) ? (string) $row['locale'] : null;
            if ($locale !== null) {
                // text_fields has locale column
                $this->query->insert(
                    "INSERT INTO `{$table}` (organization_id, entity_id, field_key, locale, `{$valueColumn}`, is_deleted, deleted_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $targetOrgId,
                        $newEntityId,
                        (string) $row['field_key'],
                        $locale,
                        $row[$valueColumn],
                        (int) $row['is_deleted'],
                        isset($row['deleted_at']) ? (string) $row['deleted_at'] : null,
                    ],
                );
            } else {
                $this->query->insert(
                    "INSERT INTO `{$table}` (organization_id, entity_id, field_key, `{$valueColumn}`, is_deleted, deleted_at)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $targetOrgId,
                        $newEntityId,
                        (string) $row['field_key'],
                        $row[$valueColumn],
                        (int) $row['is_deleted'],
                        isset($row['deleted_at']) ? (string) $row['deleted_at'] : null,
                    ],
                );
            }
            $count++;
        }

        return $count;
    }
}
