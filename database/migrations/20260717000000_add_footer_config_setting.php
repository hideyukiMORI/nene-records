<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 公開サイトフッターの内容設定 `footer_config`（#766・フッター検証 Phase 2）。
 *
 * SNS アイコンリンク・法務リンクバー（プライバシー/利用規約/特商法 等）・
 * Powered-by 表記の表示制御を保持する公開設定（JSON 文字列）。
 * `header_config`（#419）の対。自由 HTML は不採用（型付き要素のみ）。
 *
 * 既定は空＋Powered-by 表示＝既存の見た目を維持。べき等（既存はスキップ）。
 * `setting_defs` は org スコープなので組織ごとに insert。
 *
 * ⚠️ 版番はリポジトリの現行 max（20260716000000）超を手動採番
 * （migration-version date-skew 対策）。
 */
final class AddFooterConfigSetting extends AbstractMigration
{
    private const SETTING_KEY = 'footer_config';
    private const DEFAULT_VALUE = '{"social":[],"legalLinks":[],"showPoweredBy":true}';

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
                'Footer',
                $now,
                $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key = 'footer_config'");
    }
}
