<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSystemConfigTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<SQL
            CREATE TABLE IF NOT EXISTS system_config (
                `key`        VARCHAR(191)  NOT NULL,
                `value`      VARCHAR(1024) NOT NULL DEFAULT '',
                `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        // デフォルト値を挿入（single = org_slug 固定モード）
        $this->execute(<<<SQL
            INSERT IGNORE INTO system_config (`key`, `value`) VALUES
                ('tenant_resolution_mode', 'single'),
                ('tenant_org_slug',        ''),
                ('tenant_base_domain',     'localhost')
        SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS system_config');
    }
}
