<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEntityTagsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entity_tags')
            ->addColumn('entity_id', 'integer', ['null' => false])
            ->addColumn('tag_id', 'integer', ['null' => false])
            ->addForeignKey('entity_id', 'entities', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('tag_id', 'tags', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addIndex(['entity_id', 'tag_id'], ['unique' => true])
            ->create();
    }
}
