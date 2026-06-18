<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

/**
 * ランタイムテーマ（#423 Phase B）。
 *
 * ClaudeDesign が MCP（= OpenAPI 境界）経由で登録する**データ駆動テーマ**を保持。
 * `manifest` は public-theme.schema.json 準拠の JSON（tokens/flags/knobs…）。
 * `theme_key`(= manifest.id) / `name` / `version` は一覧・一意制約用に列へ複製。
 * org スコープ（マルチテナント）。built-in 静的テーマとは id 名前空間を分離する
 * （`source='runtime'`）。設計: docs/theming/runtime-themes.md。
 */
final class CreateThemesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('themes')
            ->addColumn('organization_id', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
            ->addColumn('theme_key', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 80, 'null' => false])
            ->addColumn('version', 'string', ['limit' => 32, 'null' => false, 'default' => '1.0.0'])
            ->addColumn('source', 'string', ['limit' => 16, 'null' => false, 'default' => 'runtime'])
            ->addColumn('manifest', 'text', ['null' => false, 'limit' => MysqlAdapter::TEXT_LONG])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['organization_id'])
            ->addIndex(['organization_id', 'theme_key'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('themes')->drop()->save();
    }
}
