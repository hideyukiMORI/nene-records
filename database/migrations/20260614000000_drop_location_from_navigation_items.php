<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropLocationFromNavigationItems extends AbstractMigration
{
    public function change(): void
    {
        // Items now belong to a named menu via `menu_id`; the legacy
        // header/footer/side `location` bucket (and its index) is retired.
        $this->table('navigation_items')
            ->removeIndex(['organization_id', 'location'])
            ->removeColumn('location')
            ->update();
    }
}
