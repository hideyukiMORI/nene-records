<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 公開サイトヘッダーの内容設定 `header_config`（#419 Phase C）。
 *
 * Top バー（電話 / メール / 自由テキスト）と CTA ボタン（ラベル / URL）の
 * 表示・内容を保持する公開設定（JSON 文字列）。骨格/可視性は theme_overrides の
 * スタイルフラグ側、本キーは**サイト内容**を持つ（テーマ非依存）。
 * 公開フロントが読み、Top バー行と CTA を描画する。
 *
 * 既定はすべて無効（既存の見た目を維持）。べき等（既存はスキップ）。
 * `setting_defs` は org スコープなので組織ごとに insert。
 */
final class AddHeaderConfigSetting extends AbstractMigration
{
    private const SETTING_KEY = 'header_config';
    private const DEFAULT_VALUE = '{"topbar":{"enabled":false,"phone":"","email":"","infoText":""},"cta":{"enabled":false,"label":"","url":""}}';

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
                'Header',
                $now,
                $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key = 'header_config'");
    }
}
