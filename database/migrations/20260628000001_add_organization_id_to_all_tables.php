<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddOrganizationIdToAllTables extends AbstractMigration
{
    public function up(): void
    {
        // entity_types: slug の UNIQUE を (organization_id, slug) 複合に変更
        $this->execute('
            ALTER TABLE entity_types
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_entity_types_org (organization_id),
                ADD CONSTRAINT fk_entity_types_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');
        // グローバル slug UNIQUE を削除して複合 UNIQUE に変更
        $this->execute('ALTER TABLE entity_types DROP INDEX slug');
        $this->execute('ALTER TABLE entity_types ADD UNIQUE INDEX uq_entity_types_org_slug (organization_id, slug)');

        // entities
        $this->execute('
            ALTER TABLE entities
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_entities_org (organization_id),
                ADD CONSTRAINT fk_entities_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // field_defs
        $this->execute('
            ALTER TABLE field_defs
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_field_defs_org (organization_id),
                ADD CONSTRAINT fk_field_defs_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // text_fields
        $this->execute('
            ALTER TABLE text_fields
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_text_fields_org (organization_id),
                ADD CONSTRAINT fk_text_fields_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // int_fields
        $this->execute('
            ALTER TABLE int_fields
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_int_fields_org (organization_id),
                ADD CONSTRAINT fk_int_fields_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // enum_fields
        $this->execute('
            ALTER TABLE enum_fields
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_enum_fields_org (organization_id),
                ADD CONSTRAINT fk_enum_fields_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // bool_fields
        $this->execute('
            ALTER TABLE bool_fields
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_bool_fields_org (organization_id),
                ADD CONSTRAINT fk_bool_fields_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // datetime_fields
        $this->execute('
            ALTER TABLE datetime_fields
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_datetime_fields_org (organization_id),
                ADD CONSTRAINT fk_datetime_fields_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // tags
        $this->execute('
            ALTER TABLE tags
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_tags_org (organization_id),
                ADD CONSTRAINT fk_tags_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // media
        $this->execute('
            ALTER TABLE media
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_media_org (organization_id),
                ADD CONSTRAINT fk_media_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // navigation_items
        $this->execute('
            ALTER TABLE navigation_items
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_navigation_items_org (organization_id),
                ADD CONSTRAINT fk_navigation_items_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // webhooks
        $this->execute('
            ALTER TABLE webhooks
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_webhooks_org (organization_id),
                ADD CONSTRAINT fk_webhooks_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // setting_defs: setting_key の UNIQUE を (organization_id, setting_key) 複合に変更
        $this->execute('
            ALTER TABLE setting_defs
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_setting_defs_org (organization_id),
                ADD CONSTRAINT fk_setting_defs_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');
        $this->execute('ALTER TABLE setting_defs DROP INDEX setting_key');
        $this->execute('ALTER TABLE setting_defs ADD UNIQUE INDEX uq_setting_defs_org_key (organization_id, setting_key)');

        // setting_values: FK 変更が必要（setting_key のみの FK を削除して複合に）
        $this->execute('
            ALTER TABLE setting_values
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_setting_values_org (organization_id),
                ADD CONSTRAINT fk_setting_values_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');
        $this->execute('ALTER TABLE setting_values DROP INDEX setting_key');
        $this->execute('ALTER TABLE setting_values ADD UNIQUE INDEX uq_setting_values_org_key (organization_id, setting_key)');

        // setting_revisions
        $this->execute('
            ALTER TABLE setting_revisions
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_setting_revisions_org (organization_id),
                ADD CONSTRAINT fk_setting_revisions_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // comments
        $this->execute('
            ALTER TABLE comments
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_comments_org (organization_id),
                ADD CONSTRAINT fk_comments_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // access_logs
        $this->execute('
            ALTER TABLE access_logs
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_access_logs_org (organization_id),
                ADD CONSTRAINT fk_access_logs_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // entity_revisions
        $this->execute('
            ALTER TABLE entity_revisions
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_entity_revisions_org (organization_id),
                ADD CONSTRAINT fk_entity_revisions_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');

        // entity_preview_tokens
        $this->execute('
            ALTER TABLE entity_preview_tokens
                ADD COLUMN organization_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_entity_preview_tokens_org (organization_id),
                ADD CONSTRAINT fk_entity_preview_tokens_org
                    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
        ');
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
            $this->execute("ALTER TABLE {$table} DROP FOREIGN KEY fk_{$table}_org");
            $this->execute("ALTER TABLE {$table} DROP INDEX idx_{$table}_org");
            $this->execute("ALTER TABLE {$table} DROP COLUMN organization_id");
        }

        // entity_types slug UNIQUE を復元
        $this->execute('ALTER TABLE entity_types DROP INDEX uq_entity_types_org_slug');
        $this->execute('ALTER TABLE entity_types ADD UNIQUE INDEX slug (slug)');

        // setting_defs / setting_values UNIQUE を復元
        $this->execute('ALTER TABLE setting_defs DROP INDEX uq_setting_defs_org_key');
        $this->execute('ALTER TABLE setting_defs ADD UNIQUE INDEX setting_key (setting_key)');
        $this->execute('ALTER TABLE setting_values DROP INDEX uq_setting_values_org_key');
        $this->execute('ALTER TABLE setting_values ADD UNIQUE INDEX setting_key (setting_key)');
    }
}
