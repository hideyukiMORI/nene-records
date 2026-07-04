<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 全 org に組み込み設定定義 17 種をバックフィルする（#711）。
 *
 * 従来の定義 seed は各 migration 内の「実行時点の既存 org」ループにしか無く、
 * org 作成時の seed が存在しなかったため、migration 適用後に作られた org
 * （公開セルフサーブ signup / Tier A インストーラ / superadmin CRUD）は
 * 設定定義がほぼ空だった（site_name も logo も active_theme も無い）。
 *
 * 本 migration は既存 org を修復する。以後の新規 org は
 * {@see \NeNeRecords\Setting\PdoDefaultSettingDefsSeeder}（org 作成時に seed・
 * カタログは本 migration と同一値）が担う。新しい設定を足すときは
 * 「per-org backfill migration ＋ seeder カタログ追記」の両方を行うこと。
 *
 * べき等（org × key で既存はスキップ）。旧・単一テナント期の organization_id=0
 * 行は無害な残骸としてそのまま残す（どの org からも参照されない）。
 *
 * 版数は手動採番（20260715…）: リポジトリの migration は未来日付を使っており、
 * 自動採番（当日日付）だと依存より前に sort されて fresh-DB で silent skip になるため。
 */
final class SeedSettingDefsForAllOrgs extends AbstractMigration
{
    /**
     * カタログは PdoDefaultSettingDefsSeeder::SETTING_DEFS と同一値のスナップショット
     * （migration は自己完結が原則のため意図的に重複させる）。
     */
    private const SETTING_DEFS = [
        ['setting_key' => 'site_name', 'data_type' => 'text', 'default_value' => 'NeNe Records', 'is_public' => 1, 'label' => 'Site name'],
        ['setting_key' => 'tagline', 'data_type' => 'text', 'default_value' => 'API-first flexible entity platform', 'is_public' => 1, 'label' => 'Tagline'],
        ['setting_key' => 'default_meta_description', 'data_type' => 'text', 'default_value' => '', 'is_public' => 1, 'label' => 'Default meta description'],
        ['setting_key' => 'footer_markdown', 'data_type' => 'markdown', 'default_value' => '', 'is_public' => 1, 'label' => 'Footer'],
        ['setting_key' => 'active_theme', 'data_type' => 'text', 'default_value' => 'consumer', 'is_public' => 1, 'label' => 'Public site theme'],
        ['setting_key' => 'theme_overrides', 'data_type' => 'text', 'default_value' => '{}', 'is_public' => 1, 'label' => 'Theme customizations'],
        ['setting_key' => 'logo_media_id', 'data_type' => 'media', 'default_value' => '', 'is_public' => 1, 'label' => 'Logo'],
        ['setting_key' => 'copyright_text', 'data_type' => 'text', 'default_value' => '© {year} {site}', 'is_public' => 1, 'label' => 'Copyright'],
        ['setting_key' => 'layout_config', 'data_type' => 'text', 'default_value' => '{"home":{"columns":2,"mainPos":"left","swap":false},"record":{"columns":3,"mainPos":"left","swap":false}}', 'is_public' => 1, 'label' => 'Layout'],
        ['setting_key' => 'excerpt_source', 'data_type' => 'text', 'default_value' => 'auto', 'is_public' => 0, 'label' => 'Excerpt source (auto / body / meta)'],
        ['setting_key' => 'excerpt_length', 'data_type' => 'text', 'default_value' => '160', 'is_public' => 0, 'label' => 'Excerpt length (characters)'],
        ['setting_key' => 'header_config', 'data_type' => 'text', 'default_value' => '{"topbar":{"enabled":false,"phone":"","email":"","infoText":""},"cta":{"enabled":false,"label":"","url":""}}', 'is_public' => 1, 'label' => 'Header'],
        ['setting_key' => 'home_hero', 'data_type' => 'text', 'default_value' => '[]', 'is_public' => 1, 'label' => 'Home hero'],
        ['setting_key' => 'analytics_gtm_id', 'data_type' => 'text', 'default_value' => '', 'is_public' => 1, 'label' => 'Google Tag Manager container ID'],
        ['setting_key' => 'analytics_ga4_id', 'data_type' => 'text', 'default_value' => '', 'is_public' => 1, 'label' => 'Google Analytics 4 measurement ID'],
        ['setting_key' => 'analytics_consent_default', 'data_type' => 'text', 'default_value' => 'denied', 'is_public' => 1, 'label' => 'Analytics consent default (denied/granted)'],
        ['setting_key' => 'front_page', 'data_type' => 'text', 'default_value' => '', 'is_public' => 1, 'label' => 'Front page'],
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

            foreach (self::SETTING_DEFS as $def) {
                $check->execute([$orgId, $def['setting_key']]);
                if ($check->fetch() !== false) {
                    continue;
                }
                $insert->execute([
                    $orgId,
                    $def['setting_key'],
                    $def['data_type'],
                    $def['default_value'],
                    $def['is_public'],
                    $def['label'],
                    $now,
                    $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // バックフィルの取り消しは「どの行を本 migration が入れたか」を区別できない
        // ため行わない（冪等な up の再実行に害はない）。no-op。
    }
}
