<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Per-org maintenance mode setting `maintenance_mode`（#813）。
 *
 * bool（既定 'false'）。ON のとき公開面が未ログイン来訪者に 503 メンテナンスページを返す
 * （ログイン済みは素通し）。運用フラグのため is_public=0（公開設定 API には出さない）。
 *
 * べき等（既存はスキップ）。`setting_defs` は org スコープなので組織ごとに insert。
 * 新規 org は `PdoDefaultSettingDefsSeeder` が同じ def を播種する（#711 のルール）。
 *
 * ⚠️ 版番はリポジトリの現行 max（20260719000000）超を手動採番
 * （migration-version date-skew 対策）。
 */
final class AddMaintenanceModeSetting extends AbstractMigration
{
    private const SETTING_KEY = 'maintenance_mode';
    private const DEFAULT_VALUE = 'false';

    public function up(): void
    {
        if (!$this->hasTable('organizations') || !$this->hasTable('setting_defs')) {
            return;
        }

        $pdo = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');

        $orgs = $pdo->query('SELECT id FROM organizations ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);

        $check = $pdo->prepare('SELECT id FROM setting_defs WHERE organization_id = ? AND setting_key = ?');
        $insert = $pdo->prepare(
            'INSERT INTO setting_defs (organization_id, setting_key, data_type, default_value, is_public, label, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        );

        foreach ($orgs as $orgIdRaw) {
            $orgId = (int) $orgIdRaw;
            $check->execute([$orgId, self::SETTING_KEY]);
            if ($check->fetch() !== false) {
                continue;
            }
            $insert->execute([
                $orgId,
                self::SETTING_KEY,
                'bool',
                self::DEFAULT_VALUE,
                0,
                'Maintenance mode',
                $now,
                $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key = 'maintenance_mode'");
    }
}
