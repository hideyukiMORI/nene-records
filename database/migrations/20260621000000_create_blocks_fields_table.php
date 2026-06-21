<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateBlocksFieldsTable extends AbstractMigration
{
    public function change(): void
    {
        // Mirrors text_fields (incl. the later organization_id + locale additions),
        // but `value` holds a JSON document ([{id,type,data}]); LONGTEXT so large
        // block lists are not truncated. Document shape is validated in the app
        // layer (BlocksDocumentValidator), not the DB.
        $this->table('blocks_fields')
            ->addColumn('organization_id', 'integer', ['null' => false, 'signed' => false, 'default' => 0])
            ->addColumn('entity_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('field_key', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('locale', 'string', ['limit' => 35, 'null' => true, 'default' => null])
            ->addColumn('value', 'text', ['null' => false, 'limit' => MysqlAdapter::TEXT_LONG])
            ->addColumn('is_deleted', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['organization_id'])
            ->addIndex(['entity_id'])
            ->addForeignKey('entity_id', 'entities', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }
}
