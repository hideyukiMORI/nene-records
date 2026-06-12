<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWidgetsTable extends AbstractMigration
{
    public function change(): void
    {
        // Site-level widgets placed into layout regions (sidebar / aside / ...).
        $this->table('widgets')
            ->addColumn('organization_id', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
            ->addColumn('widget_type', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('region', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('display_order', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('settings', 'text', ['null' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['organization_id'])
            ->create();
    }
}
