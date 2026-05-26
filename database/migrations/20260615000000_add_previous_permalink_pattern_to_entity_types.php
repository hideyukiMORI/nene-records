<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPreviousPermalinkPatternToEntityTypes extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entity_types')
            ->addColumn('previous_permalink_pattern', 'string', [
                'limit'   => 255,
                'null'    => true,
                'default' => null,
                'after'   => 'permalink_pattern',
            ])
            ->save();
    }
}
