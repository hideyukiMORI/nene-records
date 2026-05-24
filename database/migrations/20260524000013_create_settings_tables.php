<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSettingsTables extends AbstractMigration
{
    public function change(): void
    {
        $this->table('setting_defs')
            ->addColumn('setting_key', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('data_type', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('default_value', 'text', ['null' => true, 'default' => null])
            ->addColumn('is_public', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('label', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['setting_key'], ['unique' => true])
            ->create();

        $this->table('setting_values')
            ->addColumn('setting_key', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('value', 'text', ['null' => true, 'default' => null])
            ->addColumn('is_deleted', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['setting_key'], ['unique' => true])
            ->addForeignKey('setting_key', 'setting_defs', 'setting_key', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
            ])
            ->create();

        $this->table('setting_revisions')
            ->addColumn('setting_key', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('value', 'text', ['null' => true, 'default' => null])
            ->addColumn('previous_value', 'text', ['null' => true, 'default' => null])
            ->addColumn('action', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('actor_user_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addIndex(['setting_key', 'created_at'])
            ->addForeignKey('setting_key', 'setting_defs', 'setting_key', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
            ])
            ->create();

        $now = date('Y-m-d H:i:s');
        $rows = [
            [
                'setting_key' => 'site_name',
                'data_type' => 'text',
                'default_value' => 'NeNe Records',
                'is_public' => 1,
                'label' => 'Site name',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'tagline',
                'data_type' => 'text',
                'default_value' => 'API-first flexible entity platform',
                'is_public' => 1,
                'label' => 'Tagline',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'default_meta_description',
                'data_type' => 'text',
                'default_value' => '',
                'is_public' => 1,
                'label' => 'Default meta description',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'footer_markdown',
                'data_type' => 'markdown',
                'default_value' => '',
                'is_public' => 1,
                'label' => 'Footer',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->table('setting_defs')->insert($rows)->saveData();
    }
}
