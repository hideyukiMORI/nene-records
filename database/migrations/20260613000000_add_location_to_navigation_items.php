<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLocationToNavigationItems extends AbstractMigration
{
    public function change(): void
    {
        // Menu items gain a location (header / footer / side). Existing rows
        // default to `header` so they keep rendering where they did before.
        // No (organization_id, …) index: organization_id is added by a later-versioned
        // migration, and `location` itself is dropped again by a later migration, so the
        // index is transient. Omitting it keeps this migration forward-reference-free.
        $this->table('navigation_items')
            ->addColumn('location', 'string', ['limit' => 16, 'null' => false, 'default' => 'header', 'after' => 'url'])
            ->update();
    }
}
