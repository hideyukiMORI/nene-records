<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOrganizationsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('organizations')
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('custom_domain', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('plan', 'string', ['limit' => 32, 'null' => false, 'default' => 'free'])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['custom_domain'], ['unique' => true])
            ->create();
    }
}
