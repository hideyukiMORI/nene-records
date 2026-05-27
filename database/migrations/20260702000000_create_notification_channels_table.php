<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNotificationChannelsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('notification_channels', ['engine' => 'InnoDB', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci']);
        $table
            ->addColumn('organization_id', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('channel_type', 'enum', ['values' => ['email', 'slack', 'discord', 'chatwork', 'webhook'], 'null' => false])
            ->addColumn('label', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('is_enabled', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('config_json', 'text', ['null' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addIndex(['organization_id', 'is_enabled'])
            ->addIndex(['organization_id', 'channel_type'])
            ->create();
    }
}
