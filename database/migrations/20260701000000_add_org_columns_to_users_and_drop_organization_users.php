<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * マルチテナント基盤 Phase A — DB スキーマ変更
 *
 * 変更内容:
 *  1. users テーブルに organization_id / org_role を追加
 *     - organization_id: ユーザーが所属する組織 ID（superadmin は NULL）
 *     - org_role: 組織内ロール（admin / editor）。superadmin は全組織を管理するため不要
 *  2. organizations テーブルに external_id を追加
 *     - 外部システム（NeNe Corpus など）との連携用識別子
 *  3. organization_users テーブルを DROP
 *     - 複数組織所属を想定した設計だったが、1ユーザー=1組織方針と矛盾するため廃棄
 *     - このテーブルを使用するコードは存在しない
 */
final class AddOrgColumnsToUsersAndDropOrganizationUsers extends AbstractMigration
{
    public function up(): void
    {
        // 1. users: organization_id + org_role を追加（べき等）
        if (!$this->columnExists('users', 'organization_id')) {
            $this->execute('
                ALTER TABLE users
                    ADD COLUMN organization_id INT NULL DEFAULT NULL
                        AFTER role,
                    ADD COLUMN org_role VARCHAR(32) NULL DEFAULT NULL
                        AFTER organization_id
            ');

            $this->execute('
                ALTER TABLE users
                    ADD INDEX idx_users_org_id (organization_id)
            ');
        }

        // 2. organizations: external_id を追加（べき等）
        if (!$this->columnExists('organizations', 'external_id')) {
            $this->execute('
                ALTER TABLE organizations
                    ADD COLUMN external_id VARCHAR(255) NULL DEFAULT NULL
                        AFTER slug,
                    ADD UNIQUE INDEX idx_organizations_external_id (external_id)
            ');
        }

        // 3. organization_users テーブルを DROP（べき等）
        if ($this->hasTable('organization_users')) {
            $this->execute('DROP TABLE organization_users');
        }
    }

    public function down(): void
    {
        // organization_users を再作成
        if (!$this->hasTable('organization_users')) {
            $this->table('organization_users', ['id' => false, 'primary_key' => ['organization_id', 'user_id']])
                ->addColumn('organization_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('role', 'string', ['limit' => 32, 'null' => false, 'default' => 'admin'])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addIndex(['organization_id'])
                ->addIndex(['user_id'])
                ->addForeignKey('organization_id', 'organizations', 'id', ['delete' => 'CASCADE'])
                ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
                ->create();
        }

        // organizations: external_id を削除
        if ($this->columnExists('organizations', 'external_id')) {
            $this->execute('ALTER TABLE organizations DROP COLUMN external_id');
        }

        // users: organization_id / org_role を削除
        if ($this->columnExists('users', 'org_role')) {
            $this->execute('ALTER TABLE users DROP COLUMN org_role');
        }

        if ($this->columnExists('users', 'organization_id')) {
            $this->execute('ALTER TABLE users DROP COLUMN organization_id');
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
}
