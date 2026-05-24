<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEnumFieldsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('enum_fields')
            ->addColumn('entity_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('field_key', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('value', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('is_deleted', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['entity_id'])
            ->addForeignKey('entity_id', 'entities', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }
}
