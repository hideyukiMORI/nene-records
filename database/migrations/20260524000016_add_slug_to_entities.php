<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSlugToEntities extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entities')
            ->addColumn('slug', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'after' => 'entity_type_id',
            ])
            ->addIndex(['entity_type_id', 'slug'], ['unique' => true, 'name' => 'entities_entity_type_id_slug'])
            ->save();
    }
}
