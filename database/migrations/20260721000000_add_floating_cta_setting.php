<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Per-org floating CTA setting `floating_cta`（#982）。
 *
 * text（JSON・既定は disabled）。公開 SSR シェルが chrome として描画する第一者の
 * フローティング CTA ボタン設定。is_public=1（公開設定 API に出す＝レンダラが読む）。
 * 書き込み時は FloatingCtaValidator が P1 契約を強制（構造型・position br/bl・
 * trigger always・href scheme allowlist）。
 *
 * べき等（既存はスキップ）。`setting_defs` は org スコープなので組織ごとに insert。
 * 新規 org は `PdoDefaultSettingDefsSeeder` が同じ def を播種する（#711 のルール）。
 *
 * ⚠️ 版番はリポジトリの現行 max（20260720000000）超を手動採番
 * （migration-version date-skew 対策）。
 */
final class AddFloatingCtaSetting extends AbstractMigration
{
    private const SETTING_KEY = 'floating_cta';
    private const DEFAULT_VALUE = '{"enabled":false,"position":"br","trigger":"always","content":{"icon":"","label":"","sub":""},"link":{"url":"","newTab":true},"conditions":{"types":[],"urlGlobs":[],"exclude":[]}}';

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
                'Floating CTA',
                $now,
                $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key = 'floating_cta'");
    }
}
