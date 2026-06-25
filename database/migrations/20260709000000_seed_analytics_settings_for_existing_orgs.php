<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 計測（GA4 / GTM）＋ Consent Mode v2 の公開設定 3 件を全 org に補完する（#536 PR-A1）。
 *
 *   - analytics_gtm_id           … Google Tag Manager コンテナ ID（例 GTM-XXXXXXX）。空＝無効。
 *   - analytics_ga4_id           … GA4 測定 ID（例 G-XXXXXXXXXX）。空＝無効。
 *   - analytics_consent_default  … 同意の既定（denied / granted）。既定は EU 安全側の denied。
 *
 * いずれも公開設定（is_public=1）として、SSR ページ／SPA シェル双方が読み取り、
 * {@see \NeNeRecords\Http\WebAnalyticsConfig} で解決する。ID 設定時のみ CSP を緩め、
 * Consent Mode v2 の既定をローダ前に出力する（{@see \NeNeRecords\Http\PublicHtmlCsp::build()}）。
 *
 * `setting_defs` は org スコープなので組織ごとに insert。べき等（既存はスキップ）。
 *
 * バージョンは現行 max（20260708000000）より後にしてある。`migrations:new` は実日付で
 * 採番するため過去日付（date-skew）になり、依存より前にソートされて fresh-DB が壊れる。
 */
final class SeedAnalyticsSettingsForExistingOrgs extends AbstractMigration
{
    /** @var list<array{setting_key: string, default_value: string, label: string}> */
    private const SETTINGS = [
        ['setting_key' => 'analytics_gtm_id', 'default_value' => '', 'label' => 'Google Tag Manager container ID'],
        ['setting_key' => 'analytics_ga4_id', 'default_value' => '', 'label' => 'Google Analytics 4 measurement ID'],
        ['setting_key' => 'analytics_consent_default', 'default_value' => 'denied', 'label' => 'Analytics consent default (denied/granted)'],
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
            foreach (self::SETTINGS as $setting) {
                $check->execute([$orgId, $setting['setting_key']]);
                if ($check->fetch() !== false) {
                    continue;
                }
                $insert->execute([
                    $orgId,
                    $setting['setting_key'],
                    'text',
                    $setting['default_value'],
                    1,
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
            "DELETE FROM setting_defs WHERE setting_key IN ('analytics_gtm_id', 'analytics_ga4_id', 'analytics_consent_default')",
        );
    }
}
