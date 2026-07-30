<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

/**
 * The org-scoped surface of the schema, as a delete-ordered table list (#1002).
 *
 * `organization_id` was added across the schema as a *partition key* — a plain indexed
 * column with **no foreign key to `organizations`** (see
 * `20260628000001_add_organization_id_to_all_tables.php`). Deleting an organization row
 * therefore cascades nothing: before this list existed, `DELETE FROM organizations`
 * orphaned every child row (measured 2026-07-23 on production — 13 `entities` rows
 * survived the deletion of org 7).
 *
 * Two constraints shape the order below:
 *
 * 1. **`ON DELETE RESTRICT` FKs.** The field-value tables reference `entities`, and both
 *    `entities` and `field_defs` reference `entity_types`, with RESTRICT. Deleting a
 *    parent before its children does not orphan them — MySQL refuses the statement
 *    outright. Children must go first.
 * 2. **No reliance on engine cascade.** Rows that *are* covered by an `ON DELETE CASCADE`
 *    FK (`comments`, `entity_revisions`, `entity_preview_tokens`, `entity_relations`,
 *    `entity_tags`, `user_profiles`) are still deleted explicitly. The purge then behaves
 *    identically on any adapter and on installs where a constraint was never created, and
 *    the full set of affected tables is auditable from this one list rather than from the
 *    live schema.
 *
 * `entity_archive` was absent from the first version of this list: no migration created the
 * table and it carried no `organization_id`, so a DELETE against it would have failed on a
 * real database. #1017 created it properly — with `organization_id` from the start, exactly so
 * that an organization's deletion cannot strand its archive — and it is purged here now.
 */
final class OrganizationScopedSchema
{
    /**
     * Tables purged through a parent's org scope because they carry no `organization_id`
     * of their own. These run **first**, while both parents still exist.
     *
     * Each entry is `[table, WHERE clause with one `?` per bound org id]`.
     *
     * @var list<array{0: string, 1: string}>
     */
    public const DERIVED_PURGES = [
        ['entity_tags', 'entity_id IN (SELECT id FROM entities WHERE organization_id = ?)'],
        [
            'entity_relations',
            'source_entity_id IN (SELECT id FROM entities WHERE organization_id = ?)'
            . ' OR target_entity_id IN (SELECT id FROM entities WHERE organization_id = ?)',
        ],
        [
            'webhook_deliveries',
            'webhook_id IN (SELECT id FROM webhooks WHERE organization_id = ?)'
            . ' OR entity_id IN (SELECT id FROM entities WHERE organization_id = ?)',
        ],
        ['user_profiles', 'user_id IN (SELECT id FROM users WHERE organization_id = ?)'],
    ];

    /**
     * Every table carrying `organization_id`, ordered children-before-parents.
     *
     * Adding a table with an `organization_id` column without adding it here is a bug —
     * `OrganizationScopedSchemaCoverageTest` fails when the two drift apart.
     *
     * @var list<string>
     */
    public const ORG_SCOPED_TABLES = [
        // Entity field values — RESTRICT FK to entities, so these must precede it.
        'text_fields',
        'int_fields',
        'bool_fields',
        'enum_fields',
        'datetime_fields',
        'blocks_fields',
        // Remaining entity children (CASCADE FK; deleted explicitly — see class docblock).
        'comments',
        'entity_preview_tokens',
        'entity_revisions',
        'entities',
        // entity_type children — RESTRICT FK to entity_types, so these must precede it.
        'field_defs',
        // Snapshots of purged entities, keyed by entity_type_id (no FK). #1017 created the table
        // with organization_id precisely so an org deletion does not strand its archive.
        'entity_archive',
        'entity_types',
        // No ordering constraints among the rest.
        'access_logs',
        'media',
        // Connect-tokens another product issued to this org (#1029). Purged with the org so a
        // deleted tenant leaves no usable credential behind — revocation still belongs to the
        // issuer, but records must not keep presenting a token for an org that no longer exists.
        'org_connect_tokens',
        'menus',
        'navigation_items',
        'notification_channels',
        'setting_defs',
        'setting_revisions',
        'setting_values',
        'tags',
        'themes',
        'url_redirects',
        'users',
        'webhooks',
        'widgets',
    ];
}
