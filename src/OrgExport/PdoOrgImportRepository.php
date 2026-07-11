<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;

/**
 * Inserts an exported org payload into the target organization.
 *
 * Design (#741):
 *  - The whole import runs inside one transaction — a failure leaves the target
 *    org untouched (no partial insert).
 *  - Fresh installs are already seeded (DefaultContentTypeSeeder /
 *    DefaultSettingDefsSeeder). Rows that collide with a seeded row on their
 *    natural key (entity_types.slug, field_defs (entity_type, field_key),
 *    setting_defs/setting_values.setting_key) are MERGED onto the existing id
 *    with source-wins values, and the old id is remapped so downstream FKs follow.
 *  - INSERT column lists track the live schema (permalink / menu_order / layout,
 *    navigation_items.menu_id, media.alt_text/width/height/storage_key, …).
 *    The round-trip is exercised by tests/OrgExport/PdoOrgImportRepositoryTest so
 *    a future column addition that is not mirrored here fails CI.
 */
final readonly class PdoOrgImportRepository implements OrgImportRepositoryInterface
{
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Imports exported data into the given organization inside one transaction.
     *
     * @param  array<string, mixed> $payload  The decoded JSON export payload.
     * @return array<string, int>             Row counts per table.
     */
    public function import(int $targetOrgId, array $payload): array
    {
        /** @var array<string, int> $counts */
        $counts = $this->transactions->transactional(
            fn (DatabaseQueryExecutorInterface $query): array => $this->doImport($query, $targetOrgId, $payload),
        );

        return $counts;
    }

    /**
     * @param  array<string, mixed> $payload
     * @return array<string, int>
     */
    private function doImport(DatabaseQueryExecutorInterface $query, int $targetOrgId, array $payload): array
    {
        $counts = [];

        // ── entity_types (merge on org+slug) ───────────────────────────────
        /** @var array<int, int> $entityTypeMap  old_id → new_id */
        $entityTypeMap = [];
        foreach ((array) ($payload['entity_types'] ?? []) as $row) {
            $oldId = (int) $row['id'];
            $slug  = (string) $row['slug'];

            $existing = $query->fetchOne(
                'SELECT id FROM entity_types WHERE organization_id = ? AND slug = ?',
                [$targetOrgId, $slug],
            );

            $params = [
                (string) $row['name'],
                (int) ($row['is_pinned'] ?? 0),
                (string) ($row['default_layout'] ?? 'standard'),
                (int) ($row['display_order'] ?? 0),
                isset($row['labels']) ? (string) $row['labels'] : null,
                isset($row['permalink_pattern']) ? (string) $row['permalink_pattern'] : null,
                isset($row['previous_permalink_pattern']) ? (string) $row['previous_permalink_pattern'] : null,
            ];

            if ($existing !== null) {
                $newId = (int) $existing['id'];
                $query->execute(
                    'UPDATE entity_types
                        SET name = ?, is_pinned = ?, default_layout = ?, display_order = ?,
                            labels = ?, permalink_pattern = ?, previous_permalink_pattern = ?
                      WHERE id = ?',
                    [...$params, $newId],
                );
            } else {
                $newId = $query->insert(
                    'INSERT INTO entity_types
                        (organization_id, name, is_pinned, default_layout, display_order,
                         labels, permalink_pattern, previous_permalink_pattern, slug)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$targetOrgId, ...$params, $slug],
                );
            }
            $entityTypeMap[$oldId] = $newId;
        }
        $counts['entity_types'] = count($entityTypeMap);

        // ── field_defs (merge on org+entity_type+field_key, active) ────────
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

            $params = [
                (string) $row['data_type'],
                isset($row['region']) ? (string) $row['region'] : null,
                (int) ($row['display_order'] ?? 0),
                $newTargetEntityTypeId,
                isset($row['cardinality']) ? (string) $row['cardinality'] : null,
                (int) ($row['is_deleted'] ?? 0),
                isset($row['deleted_at']) ? (string) $row['deleted_at'] : null,
            ];

            $existing = ((int) ($row['is_deleted'] ?? 0)) === 0
                ? $query->fetchOne(
                    'SELECT id FROM field_defs
                      WHERE organization_id = ? AND entity_type_id = ? AND field_key = ? AND is_deleted = 0',
                    [$targetOrgId, $newEntityTypeId, (string) $row['field_key']],
                )
                : null;

            if ($existing !== null) {
                $newId = (int) $existing['id'];
                $query->execute(
                    'UPDATE field_defs
                        SET data_type = ?, region = ?, display_order = ?, target_entity_type_id = ?,
                            cardinality = ?, is_deleted = ?, deleted_at = ?
                      WHERE id = ?',
                    [...$params, $newId],
                );
            } else {
                $newId = $query->insert(
                    'INSERT INTO field_defs
                        (organization_id, entity_type_id, field_key, data_type, region, display_order,
                         target_entity_type_id, cardinality, is_deleted, deleted_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $targetOrgId,
                        $newEntityTypeId,
                        (string) $row['field_key'],
                        (string) $row['data_type'],
                        $params[1],
                        $params[2],
                        $newTargetEntityTypeId,
                        $params[4],
                        $params[5],
                        $params[6],
                    ],
                );
            }
            $fieldDefMap[$oldId] = $newId;
        }
        $counts['field_defs'] = count($fieldDefMap);

        // ── entities (append; carries permalink/menu_order/layout/flags) ───
        /** @var array<int, int> $entityMap  old_id → new_id */
        $entityMap = [];
        foreach ((array) ($payload['entities'] ?? []) as $row) {
            $oldId           = (int) $row['id'];
            $newEntityTypeId = $entityTypeMap[(int) $row['entity_type_id']] ?? null;
            if ($newEntityTypeId === null) {
                continue;
            }
            $newId = $query->insert(
                'INSERT INTO entities
                    (organization_id, entity_type_id, slug, permalink, menu_order, layout,
                     show_comments, show_related, status, published_at, scheduled_at,
                     meta_title, meta_description, is_deleted, created_at, updated_at, deleted_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    $newEntityTypeId,
                    isset($row['slug']) ? (string) $row['slug'] : null,
                    isset($row['permalink']) ? (string) $row['permalink'] : null,
                    (int) ($row['menu_order'] ?? 0),
                    isset($row['layout']) ? (string) $row['layout'] : null,
                    isset($row['show_comments']) ? (int) $row['show_comments'] : null,
                    isset($row['show_related']) ? (int) $row['show_related'] : null,
                    (string) ($row['status'] ?? 'draft'),
                    isset($row['published_at']) ? (string) $row['published_at'] : null,
                    isset($row['scheduled_at']) ? (string) $row['scheduled_at'] : null,
                    isset($row['meta_title']) ? (string) $row['meta_title'] : null,
                    isset($row['meta_description']) ? (string) $row['meta_description'] : null,
                    (int) ($row['is_deleted'] ?? 0),
                    isset($row['created_at']) ? (string) $row['created_at'] : null,
                    isset($row['updated_at']) ? (string) $row['updated_at'] : null,
                    isset($row['deleted_at']) ? (string) $row['deleted_at'] : null,
                ],
            );
            $entityMap[$oldId] = $newId;
        }
        $counts['entities'] = count($entityMap);

        // ── typed field values ─────────────────────────────────────────────
        $counts['text_fields']     = $this->importFieldValues($query, 'text_fields', (array) ($payload['text_fields'] ?? []), $targetOrgId, $entityMap);
        $counts['int_fields']      = $this->importFieldValues($query, 'int_fields', (array) ($payload['int_fields'] ?? []), $targetOrgId, $entityMap);
        $counts['enum_fields']     = $this->importFieldValues($query, 'enum_fields', (array) ($payload['enum_fields'] ?? []), $targetOrgId, $entityMap);
        $counts['bool_fields']     = $this->importFieldValues($query, 'bool_fields', (array) ($payload['bool_fields'] ?? []), $targetOrgId, $entityMap);
        $counts['datetime_fields'] = $this->importFieldValues($query, 'datetime_fields', (array) ($payload['datetime_fields'] ?? []), $targetOrgId, $entityMap);

        // ── tags (global slug — reuse existing) ────────────────────────────
        /** @var array<int, int> $tagMap  old_id → new_id */
        $tagMap = [];
        foreach ((array) ($payload['tags'] ?? []) as $row) {
            $oldId    = (int) $row['id'];
            $existing = $query->fetchOne(
                'SELECT id FROM tags WHERE organization_id = ? AND slug = ?',
                [$targetOrgId, (string) $row['slug']],
            );
            if ($existing !== null) {
                $tagMap[$oldId] = (int) $existing['id'];
            } else {
                $tagMap[$oldId] = $query->insert(
                    'INSERT INTO tags (organization_id, slug, name) VALUES (?, ?, ?)',
                    [$targetOrgId, (string) $row['slug'], (string) $row['name']],
                );
            }
        }
        $counts['tags'] = count($tagMap);

        // ── entity_tags ─────────────────────────────────────────────────────
        $entityTagCount = 0;
        foreach ((array) ($payload['entity_tags'] ?? []) as $row) {
            $newEntityId = $entityMap[(int) $row['entity_id']] ?? null;
            $newTagId    = $tagMap[(int) $row['tag_id']] ?? null;
            if ($newEntityId === null || $newTagId === null) {
                continue;
            }
            $query->execute(
                'INSERT INTO entity_tags (entity_id, tag_id) VALUES (?, ?)',
                [$newEntityId, $newTagId],
            );
            $entityTagCount++;
        }
        $counts['entity_tags'] = $entityTagCount;

        // ── media (append; carries alt_text/width/height/storage_key) ──────
        /** @var array<int, int> $mediaMap  old_id → new_id (available for downstream references) */
        $mediaMap   = [];
        $mediaCount = 0;
        foreach ((array) ($payload['media'] ?? []) as $row) {
            $now   = $this->clock->now()->format('Y-m-d H:i:s');
            $newId = $query->insert(
                'INSERT INTO media
                    (organization_id, original_name, stored_name, mime_type, alt_text, size, width, height, url, storage_key, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    (string) $row['original_name'],
                    (string) $row['stored_name'],
                    (string) $row['mime_type'],
                    isset($row['alt_text']) ? (string) $row['alt_text'] : null,
                    (int) $row['size'],
                    isset($row['width']) ? (int) $row['width'] : null,
                    isset($row['height']) ? (int) $row['height'] : null,
                    (string) $row['url'],
                    (string) ($row['storage_key'] ?? ''),
                    (string) ($row['created_at'] ?? $now),
                ],
            );
            if (isset($row['id'])) {
                $mediaMap[(int) $row['id']] = $newId;
            }
            $mediaCount++;
        }
        $counts['media'] = $mediaCount;

        // ── menus (merge on org+slug; build menuMap for FKs) (#347) ────────
        /** @var array<int, int> $menuMap  old_id → new_id */
        $menuMap    = [];
        $menuCount  = 0;
        foreach ((array) ($payload['menus'] ?? []) as $row) {
            $oldId    = (int) $row['id'];
            $now      = $this->clock->now()->format('Y-m-d H:i:s');
            $existing = $query->fetchOne(
                'SELECT id FROM menus WHERE organization_id = ? AND slug = ?',
                [$targetOrgId, (string) $row['slug']],
            );
            if ($existing !== null) {
                $newId = (int) $existing['id'];
                $query->execute(
                    'UPDATE menus SET name = ?, location = ?, updated_at = ? WHERE id = ?',
                    [
                        (string) $row['name'],
                        isset($row['location']) ? (string) $row['location'] : null,
                        $now,
                        $newId,
                    ],
                );
            } else {
                $newId = $query->insert(
                    'INSERT INTO menus (organization_id, name, slug, location, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [
                        $targetOrgId,
                        (string) $row['name'],
                        (string) $row['slug'],
                        isset($row['location']) ? (string) $row['location'] : null,
                        (string) ($row['created_at'] ?? $now),
                        (string) ($row['updated_at'] ?? $now),
                    ],
                );
            }
            $menuMap[$oldId] = $newId;
            $menuCount++;
        }
        $counts['menus'] = $menuCount;

        // ── navigation_items (append; menu_id remapped via menuMap) ────────
        $navCount = 0;
        foreach ((array) ($payload['navigation_items'] ?? []) as $row) {
            $now       = $this->clock->now()->format('Y-m-d H:i:s');
            $newMenuId = isset($row['menu_id'])
                ? ($menuMap[(int) $row['menu_id']] ?? null)
                : null;
            $query->insert(
                'INSERT INTO navigation_items
                    (organization_id, menu_id, label, url, display_order, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    $newMenuId,
                    (string) $row['label'],
                    (string) $row['url'],
                    (int) ($row['display_order'] ?? 0),
                    (string) ($row['created_at'] ?? $now),
                    (string) ($row['updated_at'] ?? $now),
                ],
            );
            $navCount++;
        }
        $counts['navigation_items'] = $navCount;

        // ── widgets (append; remap settings.menuId for menu widgets) (#324) ─
        $widgetCount = 0;
        foreach ((array) ($payload['widgets'] ?? []) as $row) {
            $now      = $this->clock->now()->format('Y-m-d H:i:s');
            $settings = $this->remapWidgetSettings(
                isset($row['settings']) ? (string) $row['settings'] : null,
                (string) $row['widget_type'],
                $menuMap,
            );
            $query->insert(
                'INSERT INTO widgets (organization_id, widget_type, region, display_order, title, settings, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    (string) $row['widget_type'],
                    (string) $row['region'],
                    (int) ($row['display_order'] ?? 0),
                    isset($row['title']) ? (string) $row['title'] : null,
                    $settings,
                    (string) ($row['created_at'] ?? $now),
                    (string) ($row['updated_at'] ?? $now),
                ],
            );
            $widgetCount++;
        }
        $counts['widgets'] = $widgetCount;

        // ── themes (merge on org+theme_key; manifest is self-contained) ────
        $themeCount = 0;
        foreach ((array) ($payload['themes'] ?? []) as $row) {
            $now      = $this->clock->now()->format('Y-m-d H:i:s');
            $existing = $query->fetchOne(
                'SELECT id FROM themes WHERE organization_id = ? AND theme_key = ?',
                [$targetOrgId, (string) $row['theme_key']],
            );
            if ($existing !== null) {
                $query->execute(
                    'UPDATE themes SET name = ?, version = ?, source = ?, manifest = ?, updated_at = ? WHERE id = ?',
                    [
                        (string) $row['name'],
                        (string) ($row['version'] ?? '1.0.0'),
                        (string) ($row['source'] ?? 'runtime'),
                        (string) $row['manifest'],
                        $now,
                        (int) $existing['id'],
                    ],
                );
            } else {
                $query->insert(
                    'INSERT INTO themes (organization_id, theme_key, name, version, source, manifest, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $targetOrgId,
                        (string) $row['theme_key'],
                        (string) $row['name'],
                        (string) ($row['version'] ?? '1.0.0'),
                        (string) ($row['source'] ?? 'runtime'),
                        (string) $row['manifest'],
                        (string) ($row['created_at'] ?? $now),
                        (string) ($row['updated_at'] ?? $now),
                    ],
                );
            }
            $themeCount++;
        }
        $counts['themes'] = $themeCount;

        // ── blocks_fields (append; entity_id remapped) (#486) ──────────────
        // NOTE: the block body may embed media URLs / entity references. Those
        // are NOT rewritten here (see #741 Phase 2 design note / follow-up issue);
        // the body is imported verbatim so block content survives, and relative
        // media URLs resolve because Tier A serves media from the same paths.
        $blocksCount = 0;
        foreach ((array) ($payload['blocks_fields'] ?? []) as $row) {
            $newEntityId = $entityMap[(int) $row['entity_id']] ?? null;
            if ($newEntityId === null) {
                continue;
            }
            $query->insert(
                'INSERT INTO blocks_fields (organization_id, entity_id, field_key, locale, value, is_deleted, deleted_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    $newEntityId,
                    (string) $row['field_key'],
                    isset($row['locale']) ? (string) $row['locale'] : null,
                    (string) $row['value'],
                    (int) ($row['is_deleted'] ?? 0),
                    isset($row['deleted_at']) ? (string) $row['deleted_at'] : null,
                ],
            );
            $blocksCount++;
        }
        $counts['blocks_fields'] = $blocksCount;

        // ── entity_relations (remap source/target entity) (#200/#542) ──────
        $relationCount = 0;
        foreach ((array) ($payload['entity_relations'] ?? []) as $row) {
            $newSource = $entityMap[(int) $row['source_entity_id']] ?? null;
            $newTarget = $entityMap[(int) $row['target_entity_id']] ?? null;
            if ($newSource === null || $newTarget === null) {
                continue;
            }
            $query->insert(
                'INSERT INTO entity_relations (source_entity_id, target_entity_id, field_key)
                 VALUES (?, ?, ?)',
                [$newSource, $newTarget, (string) $row['field_key']],
            );
            $relationCount++;
        }
        $counts['entity_relations'] = $relationCount;

        // ── url_redirects (append) (#188/#565) ─────────────────────────────
        $redirectCount = 0;
        foreach ((array) ($payload['url_redirects'] ?? []) as $row) {
            $now = $this->clock->now()->format('Y-m-d H:i:s');
            $query->insert(
                'INSERT INTO url_redirects (organization_id, source_path, target_path, created_at)
                 VALUES (?, ?, ?, ?)',
                [
                    $targetOrgId,
                    (string) $row['source_path'],
                    (string) $row['target_path'],
                    (string) ($row['created_at'] ?? $now),
                ],
            );
            $redirectCount++;
        }
        $counts['url_redirects'] = $redirectCount;

        // ── setting_defs (merge on org+setting_key, source-wins) ───────────
        $settingDefCount = 0;
        foreach ((array) ($payload['setting_defs'] ?? []) as $row) {
            $now      = $this->clock->now()->format('Y-m-d H:i:s');
            $existing = $query->fetchOne(
                'SELECT id FROM setting_defs WHERE organization_id = ? AND setting_key = ?',
                [$targetOrgId, (string) $row['setting_key']],
            );
            if ($existing !== null) {
                $query->execute(
                    'UPDATE setting_defs SET data_type = ?, default_value = ?, is_public = ?, label = ?, updated_at = ?
                      WHERE id = ?',
                    [
                        (string) $row['data_type'],
                        isset($row['default_value']) ? (string) $row['default_value'] : null,
                        (int) $row['is_public'],
                        (string) $row['label'],
                        $now,
                        (int) $existing['id'],
                    ],
                );
            } else {
                $query->insert(
                    'INSERT INTO setting_defs (organization_id, setting_key, data_type, default_value, is_public, label, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $targetOrgId,
                        (string) $row['setting_key'],
                        (string) $row['data_type'],
                        isset($row['default_value']) ? (string) $row['default_value'] : null,
                        (int) $row['is_public'],
                        (string) $row['label'],
                        (string) ($row['created_at'] ?? $now),
                        (string) ($row['updated_at'] ?? $now),
                    ],
                );
            }
            $settingDefCount++;
        }
        $counts['setting_defs'] = $settingDefCount;

        // ── setting_values (merge on org+setting_key, source-wins) ─────────
        // logo_media_id points at a media row id — remap it via the media map so
        // the logo survives transport. Other settings that embed media URLs or
        // entity references inside JSON blobs (home_hero, footer_config, …) are
        // NOT rewritten here (see #741 Phase 2 design note / follow-up issue).
        $settingValueCount = 0;
        foreach ((array) ($payload['setting_values'] ?? []) as $row) {
            $now       = $this->clock->now()->format('Y-m-d H:i:s');
            $settingKey = (string) $row['setting_key'];
            $value      = isset($row['value']) ? (string) $row['value'] : null;
            if ($settingKey === 'logo_media_id' && $value !== null && $value !== '' && ctype_digit($value)) {
                $value = isset($mediaMap[(int) $value]) ? (string) $mediaMap[(int) $value] : $value;
            }
            $existing = $query->fetchOne(
                'SELECT id FROM setting_values WHERE organization_id = ? AND setting_key = ?',
                [$targetOrgId, $settingKey],
            );
            if ($existing !== null) {
                $query->execute(
                    'UPDATE setting_values SET value = ?, is_deleted = ?, deleted_at = ?, updated_at = ?
                      WHERE id = ?',
                    [
                        $value,
                        (int) ($row['is_deleted'] ?? 0),
                        isset($row['deleted_at']) ? (string) $row['deleted_at'] : null,
                        $now,
                        (int) $existing['id'],
                    ],
                );
            } else {
                $query->insert(
                    'INSERT INTO setting_values (organization_id, setting_key, value, is_deleted, deleted_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        $targetOrgId,
                        $settingKey,
                        $value,
                        (int) ($row['is_deleted'] ?? 0),
                        isset($row['deleted_at']) ? (string) $row['deleted_at'] : null,
                        (string) ($row['created_at'] ?? $now),
                        (string) ($row['updated_at'] ?? $now),
                    ],
                );
            }
            $settingValueCount++;
        }
        $counts['setting_values'] = $settingValueCount;

        return $counts;
    }

    /**
     * Remaps the `menuId` inside a menu widget's settings JSON via the menu map.
     *
     * Menu widgets (#324) store `{"menuId": <id>, ...}` pointing at a menus row.
     * Other widget types carry no cross-table id, so their settings pass through
     * untouched. Malformed / non-object JSON is returned verbatim.
     *
     * @param array<int, int> $menuMap  old menu id → new menu id
     */
    private function remapWidgetSettings(?string $settings, string $widgetType, array $menuMap): ?string
    {
        if ($settings === null || $settings === '' || $widgetType !== 'menu') {
            return $settings;
        }

        $decoded = json_decode($settings, true);
        if (!is_array($decoded) || !isset($decoded['menuId'])) {
            return $settings;
        }

        $oldMenuId = (int) $decoded['menuId'];
        if (!isset($menuMap[$oldMenuId])) {
            return $settings;
        }

        $decoded['menuId'] = $menuMap[$oldMenuId];
        $encoded           = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? $settings : $encoded;
    }

    /**
     * Inserts field value rows (text/int/enum/bool/datetime) for the given table.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, int>                  $entityMap  old entity_id → new entity_id
     */
    private function importFieldValues(
        DatabaseQueryExecutorInterface $query,
        string $table,
        array $rows,
        int $targetOrgId,
        array $entityMap,
    ): int {
        $count = 0;
        foreach ($rows as $row) {
            $newEntityId = $entityMap[(int) $row['entity_id']] ?? null;
            if ($newEntityId === null) {
                continue;
            }
            $locale = isset($row['locale']) ? (string) $row['locale'] : null;
            if ($locale !== null) {
                $query->insert(
                    "INSERT INTO `{$table}` (organization_id, entity_id, field_key, locale, `value`, is_deleted, deleted_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $targetOrgId,
                        $newEntityId,
                        (string) $row['field_key'],
                        $locale,
                        $row['value'],
                        (int) ($row['is_deleted'] ?? 0),
                        isset($row['deleted_at']) ? (string) $row['deleted_at'] : null,
                    ],
                );
            } else {
                $query->insert(
                    "INSERT INTO `{$table}` (organization_id, entity_id, field_key, `value`, is_deleted, deleted_at)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $targetOrgId,
                        $newEntityId,
                        (string) $row['field_key'],
                        $row['value'],
                        (int) ($row['is_deleted'] ?? 0),
                        isset($row['deleted_at']) ? (string) $row['deleted_at'] : null,
                    ],
                );
            }
            $count++;
        }

        return $count;
    }
}
