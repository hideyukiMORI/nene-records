<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEntitiesTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entities')
            ->addColumn('entity_type_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('is_deleted', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['entity_type_id'])
            ->addForeignKey('entity_type_id', 'entity_types', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }
}
