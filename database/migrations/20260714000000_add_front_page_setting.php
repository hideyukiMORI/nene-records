<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 公開トップに表示するレコードを指定する設定 `front_page`（#701 / P4 フロントページ設定）。
 *
 * WordPress の「設定 → 表示設定 → ホームページの表示 = 固定ページ」に相当。値は
 * 「トップページに表示するレコード（entities.id）の数字文字列」。空 = 従来どおりの
 * マガジン風トップ（最新レコード横断フィード）にフォールバックする。
 *
 * 公開設定（`is_public=1`）だが、公開配信時は ID ではなく解決済み canonical パスとして
 * 返す（{@see \NeNeRecords\Setting\ListPublicSettingsUseCase}）。既定は空文字。べき等
 * （既存はスキップ）。`setting_defs` は org スコープなので組織ごとに insert。
 */
final class AddFrontPageSetting extends AbstractMigration
{
    private const SETTING_KEY = 'front_page';
    private const DEFAULT_VALUE = '';

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
                'Front page',
                $now,
                $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key = 'front_page'");
    }
}
