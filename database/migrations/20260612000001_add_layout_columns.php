<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLayoutColumns extends AbstractMigration
{
    public function change(): void
    {
        // Per-type default layout (scaffold preset for public pages).
        $this->table('entity_types')
            ->addColumn('default_layout', 'string', ['limit' => 32, 'null' => false, 'default' => 'standard', 'after' => 'slug'])
            ->update();

        // Per-entity override; NULL = inherit the type's default_layout.
        $this->table('entities')
            ->addColumn('layout', 'string', ['limit' => 32, 'null' => true, 'default' => null, 'after' => 'slug'])
            ->update();
    }
}
