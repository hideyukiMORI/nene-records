<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * org 単位のファビコン `favicon_media_id`（#1073）。
 *
 * これが無かったため、製品既定と違うファビコンを出したい設置は
 * `templates/public/record-detail.php` と `type-archive.php` の `<link rel="icon">` を
 * **サーバ上で直接書き換える**しか手が無かった。ayane.co.jp が実際にそうなっており、
 * その改変は git のどのコミットにも存在しないため、配備のたびに製品既定へ黙って戻り、
 * 戻った事実もどこにも記録されなかった（2026-09-05 実測）。
 *
 * 値は org スコープの**メディア URL**（`logo_media_id` / `default_og_image` と同形式）。
 * 公開 SSR が {@see \NeNeRecords\PublicRecord\PublicFaviconLinks} 経由で解決する。
 * SVG は原本のまま、ラスタは `icon32` / `icon180` / `icon192` の派生に解決される。
 *
 * 既定は空文字（＝製品既定のファビコンを出す＝現状維持）。べき等（既存はスキップ）。
 * `setting_defs` は org スコープなので組織ごとに insert。
 */
final class AddFaviconSetting extends AbstractMigration
{
    private const SETTING_KEY = 'favicon_media_id';

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
                'media',
                '',
                1,
                'Favicon',
                $now,
                $now,
            ]);
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('setting_defs')) {
            return;
        }

        $this->execute("DELETE FROM setting_defs WHERE setting_key = '" . self::SETTING_KEY . "'");
    }
}
