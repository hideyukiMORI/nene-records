<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSeoFieldsToEntities extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entities')
            ->addColumn('meta_title', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after' => 'published_at'])
            ->addColumn('meta_description', 'text', ['null' => true, 'default' => null, 'after' => 'meta_title'])
            ->update();
    }
}
