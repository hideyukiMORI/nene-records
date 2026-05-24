<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPublishStatusToEntities extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entities')
            ->addColumn('status', 'string', [
                'limit' => 16,
                'null' => false,
                'default' => 'draft',
                'after' => 'entity_type_id',
            ])
            ->addColumn('published_at', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'status',
            ])
            ->addIndex(['status'])
            ->save();
    }
}
