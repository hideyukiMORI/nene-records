<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Excerpt source settings (#414 / #412 Phase B).
 *
 * The server computes a record `excerpt` (entity list `?include=excerpt`) used by
 * the public feed and post-list widgets. These admin settings drive it:
 *  - excerpt_source : auto | body | meta  (auto = meta description if set, else body)
 *  - excerpt_length : max characters (markdown stripped)
 *
 * Server-internal config (not needed by the public payload), so is_public = 0.
 * Org-scoped, inserted per organization (idempotent).
 */
final class AddExcerptSettings extends AbstractMigration
{
    /** @var list<array{setting_key: string, data_type: string, default_value: string, label: string}> */
    private const SETTINGS = [
        ['setting_key' => 'excerpt_source', 'data_type' => 'text', 'default_value' => 'auto', 'label' => 'Excerpt source (auto / body / meta)'],
        ['setting_key' => 'excerpt_length', 'data_type' => 'text', 'default_value' => '160', 'label' => 'Excerpt length (characters)'],
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
             VALUES (?, ?, ?, ?, 0, ?, ?, ?)',
        );

        foreach ($orgs as $orgIdRaw) {
            $orgId = (int) $orgIdRaw;
            foreach (self::SETTINGS as $s) {
                $check->execute([$orgId, $s['setting_key']]);
                if ($check->fetch() !== false) {
                    continue;
                }
                $insert->execute([
                    $orgId,
                    $s['setting_key'],
                    $s['data_type'],
                    $s['default_value'],
                    $s['label'],
                    $now,
                    $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key IN ('excerpt_source', 'excerpt_length')");
    }
}
