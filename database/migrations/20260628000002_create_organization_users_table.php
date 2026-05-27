<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOrganizationUsersTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('organization_users', ['id' => false, 'primary_key' => ['organization_id', 'user_id']])
            ->addColumn('organization_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('role', 'string', ['limit' => 32, 'null' => false, 'default' => 'admin'])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addIndex(['organization_id'])
            ->addIndex(['user_id'])
            ->addForeignKey('organization_id', 'organizations', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
