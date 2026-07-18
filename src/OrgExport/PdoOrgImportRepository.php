<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeNeRecords\Organization\DefaultContentTypeSeederInterface;

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
 *  - Seed residue is reconciled, not left behind (#952, found on the first real
 *    Tier A transport): source-wins extends to the field-def SET of a merged
 *    type (active target defs the source does not ship are soft-deleted —
 *    otherwise the wizard-seeded title/body defs survive the merge and render
 *    as empty "title / —" stubs on every public page), and a seeded default
 *    type (SEED_SLUGS) that the source does not ship is removed entirely when
 *    nothing references it (seeded types are pinned, so they leak into the
 *    public bootstrap's entityTypes if left in place).
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
        /** @var array<int, true> $mergedTypeIds  new_id of types that already existed on the target */
        $mergedTypeIds = [];
        /** @var array<string, true> $payloadTypeSlugs */
        $payloadTypeSlugs = [];
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
                $mergedTypeIds[$newId] = true;
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
            $entityTypeMap[$oldId]   = $newId;
            $payloadTypeSlugs[$slug] = true;
        }
        $counts['entity_types'] = count($entityTypeMap);

        // ── field_defs (merge on org+entity_type+field_key, active) ────────
        /** @var array<int, int> $fieldDefMap  old_id → new_id */
        $fieldDefMap = [];
        /** @var array<int, list<int>> $keptDefIdsByType  new_type_id → def ids shipped by the source */
        $keptDefIdsByType = [];
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
            $fieldDefMap[$oldId]                  = $newId;
            $keptDefIdsByType[$newEntityTypeId][] = $newId;
        }
        $counts['field_defs'] = count($fieldDefMap);

        $counts['field_defs_pruned']    = $this->pruneMergedTypeFieldDefs($query, $targetOrgId, $mergedTypeIds, $keptDefIdsByType);
        $counts['seeded_types_removed'] = $this->removeUntouchedSeededTypes($query, $targetOrgId, $payloadTypeSlugs);

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
        // The block body embeds media references (hero/gallery mediaId + url). They
        // are rewritten via the media map + relativized so images survive transport
        // (#795). Block bodies carry no numeric entity-id references (internal links
        // are permalink-based; relations live in entity_relations), so only media is
        // rewritten; everything else is preserved verbatim.
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
                    BlocksMediaRewriter::rewrite((string) $row['value'], $mediaMap),
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
        // Scalar id-valued settings are remapped so the reference survives transport:
        //   - logo_media_id → media row id (via mediaMap)
        //   - front_page    → published record's entity id (via entityMap, #701 /
        //                     FrontPageSetting) — otherwise the pinned front page can't
        //                     resolve on the target and needs a manual post-fix on every
        //                     move (#801).
        //   - home_hero     → a blocks document; its embedded media (hero/gallery) is
        //                     remapped + relativized like blocks_fields (#795).
        // footer_config / header_config hold only external {label,url} links (no media
        // or entity ids), so they are imported verbatim.
        $settingValueCount = 0;
        foreach ((array) ($payload['setting_values'] ?? []) as $row) {
            $now       = $this->clock->now()->format('Y-m-d H:i:s');
            $settingKey = (string) $row['setting_key'];
            $value      = isset($row['value']) ? (string) $row['value'] : null;
            if ($settingKey === 'logo_media_id' && $value !== null && $value !== '' && ctype_digit($value)) {
                $value = isset($mediaMap[(int) $value]) ? (string) $mediaMap[(int) $value] : $value;
            }
            if ($settingKey === 'front_page' && $value !== null && $value !== '' && ctype_digit($value)) {
                $value = isset($entityMap[(int) $value]) ? (string) $entityMap[(int) $value] : $value;
            }
            if ($settingKey === 'home_hero' && $value !== null && $value !== '') {
                $value = BlocksMediaRewriter::rewrite($value, $mediaMap);
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

        // ── comments (append; entity_id remapped) (#625/#796) ──────────────
        // comments has no user reference — only author_name/author_email are held,
        // so nothing to scrub. Rows whose entity is missing on the target are skipped.
        $commentCount = 0;
        foreach ((array) ($payload['comments'] ?? []) as $row) {
            $newEntityId = $entityMap[(int) $row['entity_id']] ?? null;
            if ($newEntityId === null) {
                continue;
            }
            $now = $this->clock->now()->format('Y-m-d H:i:s');
            $query->insert(
                'INSERT INTO comments
                    (organization_id, entity_id, author_name, author_email, body, is_approved, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    $newEntityId,
                    (string) $row['author_name'],
                    (string) $row['author_email'],
                    (string) $row['body'],
                    (int) ($row['is_approved'] ?? 0),
                    (string) ($row['created_at'] ?? $now),
                ],
            );
            $commentCount++;
        }
        $counts['comments'] = $commentCount;

        // ── webhooks (append; entity_type_id remapped, secret re-provisioned) (#285/#796) ─
        // The HMAC `secret` is never transported — it is inserted NULL so the operator
        // regenerates it on the target (#836 write-only). webhookMap feeds deliveries below.
        /** @var array<int, int> $webhookMap  old_id → new_id */
        $webhookMap  = [];
        $webhookCount = 0;
        foreach ((array) ($payload['webhooks'] ?? []) as $row) {
            $oldId = (int) $row['id'];
            $now   = $this->clock->now()->format('Y-m-d H:i:s');
            $newEntityTypeId = isset($row['entity_type_id'])
                ? ($entityTypeMap[(int) $row['entity_type_id']] ?? null)
                : null;
            $newId = $query->insert(
                'INSERT INTO webhooks
                    (organization_id, url, events, entity_type_id, secret, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, NULL, ?, ?, ?)',
                [
                    $targetOrgId,
                    (string) $row['url'],
                    (string) $row['events'],
                    $newEntityTypeId,
                    (int) ($row['is_active'] ?? 1),
                    (string) ($row['created_at'] ?? $now),
                    (string) ($row['updated_at'] ?? $now),
                ],
            );
            $webhookMap[$oldId] = $newId;
            $webhookCount++;
        }
        $counts['webhooks'] = $webhookCount;

        // ── webhook_deliveries (append; webhook_id/entity/entity_type remapped) (#285/#796) ─
        // The delivery-queue snapshot rides along so in-flight/failed history survives a move.
        // secret is NULL (re-provisioned like webhooks). Rows whose parent webhook or
        // referenced entity/entity_type is missing on the target are skipped (all NOT NULL).
        $deliveryCount = 0;
        foreach ((array) ($payload['webhook_deliveries'] ?? []) as $row) {
            $newWebhookId    = $webhookMap[(int) $row['webhook_id']] ?? null;
            $newEntityTypeId = $entityTypeMap[(int) $row['entity_type_id']] ?? null;
            $newEntityId     = $entityMap[(int) $row['entity_id']] ?? null;
            if ($newWebhookId === null || $newEntityTypeId === null || $newEntityId === null) {
                continue;
            }
            $now = $this->clock->now()->format('Y-m-d H:i:s');
            $query->insert(
                'INSERT INTO webhook_deliveries
                    (webhook_id, event, entity_type_id, entity_id, target_url, secret, payload, status,
                     attempts, max_attempts, next_attempt_at, last_error, response_status,
                     created_at, updated_at, delivered_at)
                 VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $newWebhookId,
                    (string) $row['event'],
                    $newEntityTypeId,
                    $newEntityId,
                    (string) $row['target_url'],
                    (string) $row['payload'],
                    (string) ($row['status'] ?? 'pending'),
                    (int) ($row['attempts'] ?? 0),
                    (int) ($row['max_attempts'] ?? 5),
                    (string) ($row['next_attempt_at'] ?? $now),
                    isset($row['last_error']) ? (string) $row['last_error'] : null,
                    isset($row['response_status']) ? (int) $row['response_status'] : null,
                    (string) ($row['created_at'] ?? $now),
                    (string) ($row['updated_at'] ?? $now),
                    isset($row['delivered_at']) ? (string) $row['delivered_at'] : null,
                ],
            );
            $deliveryCount++;
        }
        $counts['webhook_deliveries'] = $deliveryCount;

        // ── notification_channels (append; org stamped) (#796) ─────────────
        // config_json is the owner's own destination config (Slack/Discord/webhook URLs)
        // and is transported verbatim so notifications keep working on the target.
        $channelCount = 0;
        foreach ((array) ($payload['notification_channels'] ?? []) as $row) {
            $now = $this->clock->now()->format('Y-m-d H:i:s');
            $query->insert(
                'INSERT INTO notification_channels
                    (organization_id, channel_type, label, is_enabled, config_json, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $targetOrgId,
                    (string) $row['channel_type'],
                    (string) $row['label'],
                    (int) ($row['is_enabled'] ?? 1),
                    (string) $row['config_json'],
                    (string) ($row['created_at'] ?? $now),
                    (string) ($row['updated_at'] ?? $now),
                ],
            );
            $channelCount++;
        }
        $counts['notification_channels'] = $channelCount;

        // ── user_profiles (re-attach by email; users are never exported) (#704/#796) ─
        // Users carry password hashes + roles, so they are not transported. A profile is
        // re-attached only when the target org already has a user with the same email;
        // otherwise it is skipped and reported (user_profiles_skipped). user_id is UNIQUE,
        // so an existing profile for that user is updated rather than duplicated.
        $profileCount   = 0;
        $profileSkipped = 0;
        foreach ((array) ($payload['user_profiles'] ?? []) as $row) {
            $email = isset($row['user_email']) ? (string) $row['user_email'] : '';
            if ($email === '') {
                $profileSkipped++;
                continue;
            }
            $user = $query->fetchOne(
                'SELECT id FROM users WHERE email = ? AND organization_id = ?',
                [$email, $targetOrgId],
            );
            if ($user === null) {
                $profileSkipped++;
                continue;
            }
            $newUserId = (int) $user['id'];
            $now       = $this->clock->now()->format('Y-m-d H:i:s');
            $existing  = $query->fetchOne('SELECT id FROM user_profiles WHERE user_id = ?', [$newUserId]);
            if ($existing !== null) {
                $query->execute(
                    'UPDATE user_profiles SET display_name = ?, full_name = ?, job_title = ?, updated_at = ? WHERE id = ?',
                    [
                        isset($row['display_name']) ? (string) $row['display_name'] : null,
                        isset($row['full_name']) ? (string) $row['full_name'] : null,
                        isset($row['job_title']) ? (string) $row['job_title'] : null,
                        $now,
                        (int) $existing['id'],
                    ],
                );
            } else {
                $query->insert(
                    'INSERT INTO user_profiles (user_id, display_name, full_name, job_title, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [
                        $newUserId,
                        isset($row['display_name']) ? (string) $row['display_name'] : null,
                        isset($row['full_name']) ? (string) $row['full_name'] : null,
                        isset($row['job_title']) ? (string) $row['job_title'] : null,
                        (string) ($row['created_at'] ?? $now),
                        (string) ($row['updated_at'] ?? $now),
                    ],
                );
            }
            $profileCount++;
        }
        $counts['user_profiles']         = $profileCount;
        $counts['user_profiles_skipped'] = $profileSkipped;

        return $counts;
    }

    /**
     * Soft-deletes active field_defs on MERGED types that the source did not ship (#952).
     *
     * Merge semantics are source-wins; that must extend to the def SET, or the
     * wizard-seeded title/body defs survive the merge and the SPA renders them as
     * empty "title / —" stubs above every record. Soft delete (not physical) so a
     * mistaken import stays recoverable. Types created by this import only carry
     * source defs, and types the source does not ship at all are left untouched.
     *
     * @param  array<int, true>      $mergedTypeIds
     * @param  array<int, list<int>> $keptDefIdsByType
     * @return int                   Number of defs soft-deleted.
     */
    private function pruneMergedTypeFieldDefs(
        DatabaseQueryExecutorInterface $query,
        int $targetOrgId,
        array $mergedTypeIds,
        array $keptDefIdsByType,
    ): int {
        $pruned = 0;
        foreach (array_keys($mergedTypeIds) as $typeId) {
            $keptIds = $keptDefIdsByType[$typeId] ?? [];
            $sql     = 'UPDATE field_defs SET is_deleted = 1, deleted_at = ?
                      WHERE organization_id = ? AND entity_type_id = ? AND is_deleted = 0';
            $params  = [$this->clock->now()->format('Y-m-d H:i:s'), $targetOrgId, $typeId];
            if ($keptIds !== []) {
                $sql .= ' AND id NOT IN (' . implode(', ', array_fill(0, count($keptIds), '?')) . ')';
                $params = [...$params, ...$keptIds];
            }
            $pruned += $query->execute($sql, $params);
        }

        return $pruned;
    }

    /**
     * Removes seeded default types the source does not ship (#952).
     *
     * A fresh install seeds pinned Posts/Pages types; when the imported org does
     * not have them, they linger as empty pinned types and leak into the public
     * bootstrap's entityTypes. Removal is guarded three ways so it can never eat
     * real data: slug must be one of the seeder's SEED_SLUGS, no entity row
     * (deleted or not) may reference the type, and no active relation field_def
     * may target it.
     *
     * @param  array<string, true> $payloadTypeSlugs
     * @return int                 Number of seeded types removed.
     */
    private function removeUntouchedSeededTypes(
        DatabaseQueryExecutorInterface $query,
        int $targetOrgId,
        array $payloadTypeSlugs,
    ): int {
        $removed = 0;
        foreach (DefaultContentTypeSeederInterface::SEED_SLUGS as $slug) {
            if (isset($payloadTypeSlugs[$slug])) {
                continue;
            }
            $type = $query->fetchOne(
                'SELECT id FROM entity_types WHERE organization_id = ? AND slug = ?',
                [$targetOrgId, $slug],
            );
            if ($type === null) {
                continue;
            }
            $typeId = (int) $type['id'];
            $inUse  = $query->fetchOne('SELECT id FROM entities WHERE entity_type_id = ? LIMIT 1', [$typeId]);
            if ($inUse !== null) {
                continue;
            }
            $targeted = $query->fetchOne(
                'SELECT id FROM field_defs WHERE target_entity_type_id = ? AND is_deleted = 0 LIMIT 1',
                [$typeId],
            );
            if ($targeted !== null) {
                continue;
            }
            $query->execute('DELETE FROM field_defs WHERE organization_id = ? AND entity_type_id = ?', [$targetOrgId, $typeId]);
            $query->execute('DELETE FROM entity_types WHERE id = ?', [$typeId]);
            $removed++;
        }

        return $removed;
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
