<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * レコード公開ページの表示制御設定 `record_page_config`（#775）。
 *
 * コメント欄と「Keep reading / More from {type}」（関連レコード）の
 * サイト共通の既定表示を保持する公開設定（JSON 文字列）。
 * レコード個別の `entities.show_comments` / `show_related`（NULL=既定に従う）が上書きする。
 *
 * 既定は両方表示＝既存の見た目を維持。べき等（既存はスキップ）。
 * `setting_defs` は org スコープなので組織ごとに insert。
 * 新規 org は `PdoDefaultSettingDefsSeeder` が同じ def を播種する（#711 のルール）。
 *
 * ⚠️ 版番はリポジトリの現行 max（20260717000000）超を手動採番
 * （migration-version date-skew 対策）。
 */
final class AddRecordPageConfigSetting extends AbstractMigration
{
    private const SETTING_KEY = 'record_page_config';
    private const DEFAULT_VALUE = '{"comments":true,"related":true}';

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
                'text',
                self::DEFAULT_VALUE,
                1,
                'Record page display',
                $now,
                $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key = 'record_page_config'");
    }
}
