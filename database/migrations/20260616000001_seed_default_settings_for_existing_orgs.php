<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 既存の Organization に対してデフォルト設定定義 (setting_defs) を補完する。
 *
 * 背景:
 *   - 20260524000013_create_settings_tables は organization_id カラム追加前に実行され、
 *     設定定義は org_id=0（番兵値）にのみ存在する。
 *   - 20260628000001_add_organization_id_to_all_tables でマルチテナント化したが、
 *     entity_types は 20260703 で各 org に再シードされた一方、設定は取り残された。
 *   - 結果、実 org（例 ORG_SLUG=local → org 1）では設定が空で、取得は {items: []}、
 *     更新は 404（PdoSettingRepository が organization_id で厳密スコープするため）。
 *
 * このマイグレーション:
 *   1. organizations の全 org_id に対し、既定の設定定義を Upsert する。
 *   2. べき等: (organization_id, setting_key) が既にあればスキップ。
 */
final class SeedDefaultSettingsForExistingOrgs extends AbstractMigration
{
    /** @var list<array{setting_key: string, data_type: string, default_value: string, is_public: int, label: string}> */
    private const DEFAULT_SETTINGS = [
        ['setting_key' => 'site_name', 'data_type' => 'text', 'default_value' => 'NeNe Records', 'is_public' => 1, 'label' => 'Site name'],
        ['setting_key' => 'tagline', 'data_type' => 'text', 'default_value' => 'API-first flexible entity platform', 'is_public' => 1, 'label' => 'Tagline'],
        ['setting_key' => 'default_meta_description', 'data_type' => 'text', 'default_value' => '', 'is_public' => 1, 'label' => 'Default meta description'],
        ['setting_key' => 'footer_markdown', 'data_type' => 'markdown', 'default_value' => '', 'is_public' => 1, 'label' => 'Footer'],
        ['setting_key' => 'active_theme', 'data_type' => 'text', 'default_value' => 'consumer', 'is_public' => 1, 'label' => 'Public site theme'],
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
            foreach (self::DEFAULT_SETTINGS as $setting) {
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
        // 補完用マイグレーションのためロールバックは何もしない。
    }
}
