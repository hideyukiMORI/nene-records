<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 公開トップの hero 設定 `home_hero`（#486 S6 / EPIC ブロックシステム）。
 *
 * ホームの masthead を、型付き hero ブロックで上書きできるようにする公開設定。
 * 値は **1 要素のブロックドキュメント**（`[{id,type:"hero",data}]`）の JSON 文字列。
 * 未設定（空配列 `[]`）のときは従来のサイト統計から生成する hero にフォールバックする。
 * 公開フロントが読み、{@see \NeNeRecords\BlocksField\BlocksRenderer} 同等で描画する。
 *
 * 既定は空配列（フォールバック維持）。べき等（既存はスキップ）。
 * `setting_defs` は org スコープなので組織ごとに insert。
 */
final class AddHomeHeroSetting extends AbstractMigration
{
    private const SETTING_KEY = 'home_hero';
    private const DEFAULT_VALUE = '[]';

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
                'Home hero',
                $now,
                $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key = 'home_hero'");
    }
}
