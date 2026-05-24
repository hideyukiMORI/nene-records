<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTagsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('tags')
            ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addIndex(['slug'], ['unique' => true])
            ->create();
    }
}
