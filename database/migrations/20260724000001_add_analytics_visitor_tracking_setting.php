<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Per-org opt-in for Path B visitor analytics — `analytics_visitor_tracking` (ADR 0006 / #1007).
 *
 * Boolean setting, default 'false' (opt-in OFF): when OFF the access-log middleware behaves
 * exactly as before (no visitor_hash / referer_host / utm / UA-class computed). Operational
 * flag — not exposed via the public settings API (is_public 0), like `maintenance_mode`.
 *
 * `setting_defs` is org-scoped, so insert one row per organization. Idempotent (skips
 * existing). New orgs get this via PdoDefaultSettingDefsSeeder (the catalog duplicates this
 * value by design — migrations must stay self-contained snapshots).
 */
final class AddAnalyticsVisitorTrackingSetting extends AbstractMigration
{
    private const SETTING_KEY = 'analytics_visitor_tracking';
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
                'Visitor analytics tracking',
                $now,
                $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key = 'analytics_visitor_tracking'");
    }
}
