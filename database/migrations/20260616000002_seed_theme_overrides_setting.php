<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 既存の Organization に `theme_overrides` 公開設定を補完する（Phase 2 / #372）。
 *
 * テーマカスタマイザのノブ上書き値を **テーマ別 JSON** で保持する設定キー。
 * 公開フロントが読み、アクティブテーマの上書きを CSS 変数として適用する。
 * 既定は空 JSON `{}`。べき等（既存はスキップ）。
 */
final class SeedThemeOverridesSetting extends AbstractMigration
{
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
            $check->execute([$orgId, 'theme_overrides']);
            if ($check->fetch() !== false) {
                continue;
            }
            $insert->execute([
                $orgId,
                'theme_overrides',
                'text',
                '{}',
                1,
                'Theme customizations',
                $now,
                $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key = 'theme_overrides'");
    }
}
