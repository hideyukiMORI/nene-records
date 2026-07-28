<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates `entity_archive` — the table the archive repository has always written to (#1017).
 *
 * `PdoEntityArchiveRepository::archiveAndPurgeSoftDeleted()` snapshots soft-deleted entities
 * here before purging them, and `DeleteEntityTypeUseCase` calls it on every entity type
 * deletion. **No migration ever created the table**: it existed only as
 * `database/schema/entity_archive.sql`, which is a test snapshot that nothing executes
 * against a real database (the installer applies migrations — `public_html/install/index.php`
 * states the migrations are the source of truth). Deleting an entity type that owned at least
 * one soft-deleted entity therefore hit `no such table: entity_archive` and returned 500.
 * Reproduced 2026-07-29; no data was lost because the INSERT precedes every DELETE.
 *
 * `organization_id` is present from the start, unlike the old snapshot. Without it the rows
 * would survive their organization's deletion exactly the way #1002 described — and
 * `OrganizationScopedSchema` could not purge them.
 */
final class CreateEntityArchiveTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entity_archive')
            ->addColumn('organization_id', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('original_entity_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('entity_type_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('entity_type_slug', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('entity_type_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('entity_slug', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('entity_status', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('archived_at', 'datetime', ['null' => false])
            ->addColumn('archived_reason', 'string', ['limit' => 64, 'null' => false, 'default' => 'entity_type_deleted'])
            ->addColumn('snapshot', 'json', ['null' => false])
            ->addIndex(['organization_id'], ['name' => 'idx_entity_archive_org'])
            ->addIndex(['entity_type_id'], ['name' => 'idx_entity_archive_entity_type_id'])
            ->addIndex(['original_entity_id'], ['name' => 'idx_entity_archive_original_entity_id'])
            ->create();
    }
}
