<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEntityTypesTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entity_types')
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
            ->addIndex(['slug'], ['unique' => true])
            ->create();
    }
}
