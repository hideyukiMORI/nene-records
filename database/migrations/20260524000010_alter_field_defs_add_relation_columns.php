<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterFieldDefsAddRelationColumns extends AbstractMigration
{
    public function change(): void
    {
        $this->table('field_defs')
            ->addColumn('target_entity_type_id', 'integer', ['null' => true, 'signed' => false, 'after' => 'data_type'])
            ->addColumn('cardinality', 'string', ['null' => true, 'limit' => 16, 'after' => 'target_entity_type_id'])
            ->addForeignKey('target_entity_type_id', 'entity_types', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->update();
    }
}
