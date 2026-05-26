<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPermalinkPatternToEntityTypes extends AbstractMigration
{
    public function up(): void
    {
        $this->table('entity_types')
            ->addColumn('permalink_pattern', 'string', [
                'limit'   => 255,
                'null'    => true,
                'default' => null,
                'after'   => 'labels',
            ])
            ->save();
    }

    public function down(): void
    {
        $this->table('entity_types')
            ->removeColumn('permalink_pattern')
            ->save();
    }
}
