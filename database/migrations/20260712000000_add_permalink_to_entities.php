<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Per-record custom permalink (#651): a free-text URL path that may contain "/"
 * and, when set, overrides the entity type's permalink pattern as the record's
 * canonical public URL.
 *
 * Unique per organization. A plain UNIQUE index is correct here because MySQL
 * treats multiple NULLs as distinct, so many records may keep a NULL permalink
 * (the default — they use the per-type pattern) while every non-NULL value stays
 * unique within the org.
 */
final class AddPermalinkToEntities extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entities')
            ->addColumn('permalink', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'after' => 'slug',
            ])
            ->addIndex(['organization_id', 'permalink'], [
                'unique' => true,
                'name' => 'entities_organization_id_permalink',
            ])
            ->save();
    }
}
