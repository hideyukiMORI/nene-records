<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 公開ソーシャルカードの既定画像 `default_og_image`（#912）。
 *
 * `image` フィールドを持たないページ（bespoke の 1 html フィールド・トップ・text 主体の
 * レコード・型アーカイブ）では og:image が出せず、SNS/チャット共有時にカード画像が空になる。
 * この設定は org スコープの**メディア id**（`logo_media_id` と同形式）で、レコード側で
 * og 画像を解決できないときのフォールバックになる。空（未設定）なら従来どおり画像なし。
 * 公開 SSR が {@see \NeNeRecords\PublicRecord\DefaultOgImageResolver} 経由で og 派生に解決する。
 *
 * 既定は空文字（フォールバック無効＝現状維持）。べき等（既存はスキップ）。
 * `setting_defs` は org スコープなので組織ごとに insert。
 */
final class AddDefaultOgImageSetting extends AbstractMigration
{
    private const SETTING_KEY = 'default_og_image';

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
                'Default social image (og:image)',
                $now,
                $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key = 'default_og_image'");
    }
}
