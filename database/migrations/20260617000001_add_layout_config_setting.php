<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Per-page public layout config — Phase A (#408).
 *
 * Adds a public `layout_config` setting (JSON) so the top page's column layout
 * (sidebar / aside on/off) is configured in the admin layout builder and applied
 * to the public site — previously the builder only kept it in localStorage.
 *
 * Default mirrors the prior public behaviour: home = 2 columns (a sidebar may
 * show when sidebar widgets exist; no aside), record = 3 (record detail still
 * uses its own per-entity/type layout publicly; this is the admin preview seed).
 *
 * `setting_defs` is org-scoped, so insert per organization (idempotent).
 */
final class AddLayoutConfigSetting extends AbstractMigration
{
    private const SETTING_KEY = 'layout_config';
    private const DEFAULT_VALUE = '{"home":{"columns":2,"mainPos":"left","swap":false},"record":{"columns":3,"mainPos":"left","swap":false}}';

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
                'Layout',
                $now,
                $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key = 'layout_config'");
    }
}
