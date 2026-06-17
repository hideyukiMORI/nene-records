<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Header/footer "chrome" settings — Phase 1 (#398).
 *
 * Adds two public, typed settings the public-site shell renders theme-agnostically:
 *  - logo_media_id  : media reference (resolved to a URL in the public settings API);
 *                     empty → the shell falls back to the built-in brand mark.
 *  - copyright_text : free text with `{year}` / `{site}` tokens substituted at render.
 *
 * `setting_defs` is org-scoped (see SeedDefaultSettingsForExistingOrgs), so the
 * defs must be inserted per organization. Idempotent: skips (org, key) pairs
 * that already exist.
 */
final class AddChromeSettings extends AbstractMigration
{
    /** @var list<array{setting_key: string, data_type: string, default_value: string, is_public: int, label: string}> */
    private const CHROME_SETTINGS = [
        ['setting_key' => 'logo_media_id', 'data_type' => 'media', 'default_value' => '', 'is_public' => 1, 'label' => 'Logo'],
        ['setting_key' => 'copyright_text', 'data_type' => 'text', 'default_value' => '© {year} {site}', 'is_public' => 1, 'label' => 'Copyright'],
    ];

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
            foreach (self::CHROME_SETTINGS as $setting) {
                $check->execute([$orgId, $setting['setting_key']]);
                if ($check->fetch() !== false) {
                    continue;
                }
                $insert->execute([
                    $orgId,
                    $setting['setting_key'],
                    $setting['data_type'],
                    $setting['default_value'],
                    $setting['is_public'],
                    $setting['label'],
                    $now,
                    $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $this->execute(
            "DELETE FROM setting_defs WHERE setting_key IN ('logo_media_id', 'copyright_text')",
        );
    }
}
