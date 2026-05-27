<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserProfilesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('user_profiles', ['engine' => 'InnoDB', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci']);
        $table
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('display_name', 'string', ['limit' => 100, 'null' => true, 'default' => null])
            ->addColumn('full_name', 'string', ['limit' => 200, 'null' => true, 'default' => null])
            ->addColumn('job_title', 'string', ['limit' => 100, 'null' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addIndex(['user_id'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
