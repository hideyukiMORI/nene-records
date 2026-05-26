<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddIsPinnedToEntityTypes extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entity_types')
            ->addColumn('is_pinned', 'boolean', ['null' => false, 'default' => false, 'after' => 'slug'])
            ->update();
    }
}
