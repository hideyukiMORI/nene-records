<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddScheduledAtToEntities extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entities')
            ->addColumn('scheduled_at', 'datetime', ['null' => true, 'default' => null, 'after' => 'published_at'])
            ->update();
    }
}
