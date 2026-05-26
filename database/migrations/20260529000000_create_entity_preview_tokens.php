<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEntityPreviewTokens extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entity_preview_tokens')
            ->addColumn('entity_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('token', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('expires_at', 'datetime', ['null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addForeignKey('entity_id', 'entities', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addIndex('token', ['unique' => true])
            ->addIndex('entity_id')
            ->create();
    }
}
