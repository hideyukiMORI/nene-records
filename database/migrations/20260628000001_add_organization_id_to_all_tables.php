<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddOrganizationIdToAllTables extends AbstractMigration
{
    /**
     * organization_id はパーティションキーとして使用。
     * DEFAULT 0 = 「共有 / シングルテナント」の番兵値のため DB レベルの FK は設けない。
     * 途中失敗後の再実行に対してべき等になるよう INFORMATION_SCHEMA でチェックしてから実行する。
     */
    public function up(): void
    {
        // カラムが存在しないテーブルにのみ organization_id + index を追加
        $simpleTables = [
            'entity_types'          => 'idx_entity_types_org',
            'entities'              => 'idx_entities_org',
            'field_defs'            => 'idx_field_defs_org',
            'text_fields'           => 'idx_text_fields_org',
            'int_fields'            => 'idx_int_fields_org',
            'enum_fields'           => 'idx_enum_fields_org',
            'bool_fields'           => 'idx_bool_fields_org',
            'datetime_fields'       => 'idx_datetime_fields_org',
            'tags'                  => 'idx_tags_org',
            'media'                 => 'idx_media_org',
            'navigation_items'      => 'idx_navigation_items_org',
            'webhooks'              => 'idx_webhooks_org',
            'comments'              => 'idx_comments_org',
            'access_logs'           => 'idx_access_logs_org',
            'entity_revisions'      => 'idx_entity_revisions_org',
            'entity_preview_tokens' => 'idx_entity_preview_tokens_org',
        ];

        foreach ($simpleTables as $table => $indexName) {
            if (!$this->columnExists($table, 'organization_id')) {
                $this->execute("
                    ALTER TABLE `{$table}`
                        ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                        ADD INDEX `{$indexName}` (organization_id)
                ");
            }
        }

        // entity_types: slug UNIQUE を (organization_id, slug) 複合に変更
        if (!$this->indexExists('entity_types', 'uq_entity_types_org_slug')) {
            $this->execute('ALTER TABLE entity_types ADD UNIQUE INDEX uq_entity_types_org_slug (organization_id, slug)');
        }
        if ($this->indexExists('entity_types', 'slug')) {
            $this->execute('ALTER TABLE entity_types DROP INDEX `slug`');
        }

        // setting_values / setting_revisions の旧 FK を先に削除
        foreach ([
            'setting_values'    => 'setting_values_ibfk_1',
            'setting_revisions' => 'setting_revisions_ibfk_1',
        ] as $table => $fkName) {
            if ($this->fkExists($table, $fkName)) {
                $this->execute("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
            }
        }

        // setting_defs: organization_id + UNIQUE 複合に変更
        if (!$this->columnExists('setting_defs', 'organization_id')) {
            $this->execute('
                ALTER TABLE setting_defs
                    ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                    ADD INDEX idx_setting_defs_org (organization_id)
            ');
        }
        if (!$this->indexExists('setting_defs', 'uq_setting_defs_org_key')) {
            $this->execute('ALTER TABLE setting_defs ADD UNIQUE INDEX uq_setting_defs_org_key (organization_id, setting_key)');
        }
        if ($this->indexExists('setting_defs', 'setting_key')) {
            $this->execute('ALTER TABLE setting_defs DROP INDEX `setting_key`');
        }

        // setting_values: organization_id + UNIQUE 複合に変更
        if (!$this->columnExists('setting_values', 'organization_id')) {
            $this->execute('
                ALTER TABLE setting_values
                    ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                    ADD INDEX idx_setting_values_org (organization_id)
            ');
        }
        if (!$this->indexExists('setting_values', 'uq_setting_values_org_key')) {
            $this->execute('ALTER TABLE setting_values ADD UNIQUE INDEX uq_setting_values_org_key (organization_id, setting_key)');
        }
        if ($this->indexExists('setting_values', 'setting_key')) {
            $this->execute('ALTER TABLE setting_values DROP INDEX `setting_key`');
        }

        // setting_revisions
        if (!$this->columnExists('setting_revisions', 'organization_id')) {
            $this->execute('
                ALTER TABLE setting_revisions
                    ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                    ADD INDEX idx_setting_revisions_org (organization_id)
            ');
        }
    }

    public function down(): void
    {
        $tables = [
            'entity_preview_tokens',
            'entity_revisions',
            'access_logs',
            'comments',
            'setting_revisions',
            'setting_values',
            'setting_defs',
            'webhooks',
            'navigation_items',
            'media',
            'tags',
            'datetime_fields',
            'bool_fields',
            'enum_fields',
            'int_fields',
            'text_fields',
            'field_defs',
            'entities',
            'entity_types',
        ];

        foreach ($tables as $table) {
            if ($this->columnExists($table, 'organization_id')) {
                $this->execute("ALTER TABLE `{$table}` DROP COLUMN organization_id");
            }
        }

        if ($this->indexExists('entity_types', 'uq_entity_types_org_slug')) {
            $this->execute('ALTER TABLE entity_types DROP INDEX uq_entity_types_org_slug');
        }
        if (!$this->indexExists('entity_types', 'slug')) {
            $this->execute('ALTER TABLE entity_types ADD UNIQUE INDEX `slug` (slug)');
        }

        if ($this->indexExists('setting_defs', 'uq_setting_defs_org_key')) {
            $this->execute('ALTER TABLE setting_defs DROP INDEX uq_setting_defs_org_key');
        }
        if (!$this->indexExists('setting_defs', 'setting_key')) {
            $this->execute('ALTER TABLE setting_defs ADD UNIQUE INDEX `setting_key` (setting_key)');
        }

        if ($this->indexExists('setting_values', 'uq_setting_values_org_key')) {
            $this->execute('ALTER TABLE setting_values DROP INDEX uq_setting_values_org_key');
        }
        if (!$this->indexExists('setting_values', 'setting_key')) {
            $this->execute('ALTER TABLE setting_values ADD UNIQUE INDEX `setting_key` (setting_key)');
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = $this->fetchRow("
            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND COLUMN_NAME = '{$column}'
            LIMIT 1
        ");
        return $row !== false;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $row = $this->fetchRow("
            SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND INDEX_NAME = '{$indexName}'
            LIMIT 1
        ");
        return $row !== false;
    }

    private function fkExists(string $table, string $fkName): bool
    {
        $row = $this->fetchRow("
            SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
              AND CONSTRAINT_NAME = '{$fkName}'
            LIMIT 1
        ");
        return $row !== false;
    }
}
