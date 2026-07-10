<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * レコード公開ページのコメント/関連レコード表示の個別上書き（#775）。
 *
 * tri-state: NULL = サイト共通設定（record_page_config）に従う /
 * 1 = 表示 / 0 = 非表示。layout の per-entity override と同型のカスケード。
 *
 * ⚠️ 版番はリポジトリの現行 max（20260717000000）超を手動採番
 * （migration-version date-skew 対策）。
 */
final class AddRecordPageFlagsToEntities extends AbstractMigration
{
    public function change(): void
    {
        $this->table('entities')
            ->addColumn('show_comments', 'boolean', [
                'null' => true,
                'default' => null,
                'after' => 'layout',
                'comment' => 'Per-record comments visibility; NULL = follow record_page_config',
            ])
            ->addColumn('show_related', 'boolean', [
                'null' => true,
                'default' => null,
                'after' => 'show_comments',
                'comment' => 'Per-record related-records visibility; NULL = follow record_page_config',
            ])
            ->update();
    }
}
