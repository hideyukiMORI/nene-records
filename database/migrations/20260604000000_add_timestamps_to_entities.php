<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTimestampsToEntities extends AbstractMigration
{
    public function up(): void
    {
        // カラム追加（MySQL）
        $this->execute(
            <<<'SQL'
                ALTER TABLE entities
                    ADD COLUMN created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER is_deleted,
                    ADD COLUMN updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at
                SQL,
        );

        // 既存レコードの created_at / updated_at を entity_revisions から補完
        $this->execute(
            <<<'SQL'
                UPDATE entities e
                SET e.created_at = (
                    SELECT MIN(r.created_at)
                    FROM entity_revisions r
                    WHERE r.entity_id = e.id
                ),
                e.updated_at = (
                    SELECT MAX(r.created_at)
                    FROM entity_revisions r
                    WHERE r.entity_id = e.id
                )
                WHERE EXISTS (
                    SELECT 1 FROM entity_revisions r WHERE r.entity_id = e.id
                )
                SQL,
        );
    }

    public function down(): void
    {
        $this->execute(
            <<<'SQL'
                ALTER TABLE entities
                    DROP COLUMN created_at,
                    DROP COLUMN updated_at
                SQL,
        );
    }
}
