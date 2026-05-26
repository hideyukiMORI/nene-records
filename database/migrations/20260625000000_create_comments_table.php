<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCommentsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('comments', ['engine' => 'InnoDB', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci']);
        $table
            ->addColumn('entity_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('author_name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('author_email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('body', 'text', ['null' => false])
            ->addColumn('is_approved', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addForeignKey('entity_id', 'entities', 'id', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->addIndex(['entity_id', 'is_approved'])
            ->create();
    }
}
