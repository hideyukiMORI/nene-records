<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMediaTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('media', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('original_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('stored_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('mime_type', 'string', ['limit' => 128, 'null' => false])
            ->addColumn('size', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('url', 'string', ['limit' => 1024, 'null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->create();
    }
}
