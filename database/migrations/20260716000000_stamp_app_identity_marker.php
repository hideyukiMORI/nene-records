<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * machine database preflight（#648）用に、この DB を NeNe Records の所有物として識別する
 * マーカー行を `nene2_app_identity` に書き込む。framework の
 * {@see \Nene2\Database\Preflight\ApplicationIdentityMarker} と同一スキーマ・同一 1 行
 * （application_id ＋ nullable tenant_id・主キー無し）。
 *
 * これにより `POST /machine/database/preflight` が候補 DB を `app_identity: match` と
 * 判定でき、同一 NENE2 ledger を持つ別アプリの DB は `mismatch` で refuse できる。既存 DB
 * は本 migration の適用で marker を後追い書き込み（backfill）する。
 *
 * application_id は {@see \NeNeRecords\Database\Preflight\PreflightIdentity::APPLICATION_ID}
 * と同値（migration 自己完結の原則で literal を意図的に複製）。tenant_id は NULL: 本アプリは
 * 共有 DB・行レベル（org_id）マルチテナントで DB 単位のテナント識別が not_applicable なため。
 *
 * 版数は手動採番（20260716…・現行 max 20260715 の次）。自動採番（当日 20260705）だと依存より
 * 前に sort され fresh-DB で silent skip になるため（migration-version-date-skew）。
 */
final class StampAppIdentityMarker extends AbstractMigration
{
    private const TABLE = 'nene2_app_identity';
    private const APPLICATION_ID = 'nene-records';

    public function up(): void
    {
        if (!$this->hasTable(self::TABLE)) {
            $this->table(self::TABLE, ['id' => false])
                ->addColumn('application_id', 'string', ['limit' => 190, 'null' => false])
                ->addColumn('tenant_id', 'string', ['limit' => 190, 'null' => true])
                ->create();
        }

        // Idempotent single-row marker, matching ApplicationIdentityMarker::stamp().
        // APPLICATION_ID is a controlled literal with no special characters.
        $this->execute(sprintf('DELETE FROM %s', self::TABLE));
        $this->execute(sprintf(
            "INSERT INTO %s (application_id, tenant_id) VALUES ('%s', NULL)",
            self::TABLE,
            self::APPLICATION_ID,
        ));
    }

    public function down(): void
    {
        if ($this->hasTable(self::TABLE)) {
            $this->table(self::TABLE)->drop()->save();
        }
    }
}
