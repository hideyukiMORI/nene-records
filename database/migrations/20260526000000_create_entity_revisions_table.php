<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEntityRevisionsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entity_revisions')
            ->addColumn('entity_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('action', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('status', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('previous_status', 'string', ['limit' => 32, 'null' => true, 'default' => null])
            ->addColumn('slug', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('previous_slug', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('actor_user_id', 'integer', ['null' => true, 'signed' => false, 'default' => null])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addIndex(['entity_id', 'created_at'])
            ->addForeignKey('entity_id', 'entities', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();
    }
}
