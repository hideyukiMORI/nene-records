<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRegionAndOrderToFieldDefs extends AbstractMigration
{
    public function change(): void
    {
        // region: which layout region this field renders into (main / sidebar / aside).
        // NULL = main. display_order: ascending order within the type.
        $this->table('field_defs')
            ->addColumn('region', 'string', ['limit' => 16, 'null' => true, 'default' => null, 'after' => 'data_type'])
            ->addColumn('display_order', 'integer', ['null' => false, 'default' => 0, 'after' => 'region'])
            ->update();
    }
}
