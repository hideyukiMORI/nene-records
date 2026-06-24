<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEntityRelationsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entity_relations')
            ->addColumn('source_entity_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('target_entity_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('field_key', 'string', ['null' => false])
            ->addIndex(['source_entity_id', 'field_key'])
            ->addIndex(['source_entity_id', 'target_entity_id', 'field_key'], ['unique' => true])
            ->addForeignKey('source_entity_id', 'entities', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('target_entity_id', 'entities', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
