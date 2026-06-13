<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLocationToNavigationItems extends AbstractMigration
{
    public function change(): void
    {
        // Menu items gain a location (header / footer / side). Existing rows
        // default to `header` so they keep rendering where they did before.
        $this->table('navigation_items')
            ->addColumn('location', 'string', ['limit' => 16, 'null' => false, 'default' => 'header', 'after' => 'url'])
            ->addIndex(['organization_id', 'location'])
            ->update();
    }
}
