<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Manual ordering for records (#659): a per-record integer that orders siblings
 * in the directory tree and public navigation / child-lists, lower first. Defaults
 * to 0 so existing rows keep their current (path-derived) order until reordered.
 */
final class AddMenuOrderToEntities extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entities')
            ->addColumn('menu_order', 'integer', [
                'null' => false,
                'default' => 0,
                'after' => 'permalink',
            ])
            ->save();
    }
}
