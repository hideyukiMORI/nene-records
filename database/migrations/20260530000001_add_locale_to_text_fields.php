<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLocaleToTextFields extends AbstractMigration
{
    public function change(): void
    {
        $this->table('text_fields')
            ->addColumn('locale', 'string', [
                'limit'   => 10,
                'null'    => true,
                'default' => null,
                'after'   => 'field_key',
            ])
            ->addIndex(['entity_id', 'locale'])
            ->update();
    }
}
