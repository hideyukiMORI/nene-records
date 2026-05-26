<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNavigationItems extends AbstractMigration
{
    public function up(): void
    {
        $this->table('navigation_items', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('label', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('url', 'string', ['limit' => 1024, 'null' => false])
            ->addColumn('display_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->create();
    }

    public function down(): void
    {
        $this->table('navigation_items')->drop()->save();
    }
}
