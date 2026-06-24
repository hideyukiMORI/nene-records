<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropLocationFromNavigationItems extends AbstractMigration
{
    public function change(): void
    {
        // Items now belong to a named menu via `menu_id`; the legacy
        // header/footer/side `location` bucket is retired. (No removeIndex: the
        // earlier migration no longer creates an org-scoped location index, and
        // dropping the column drops any remaining index on it.)
        $this->table('navigation_items')
            ->removeColumn('location')
            ->update();
    }
}
