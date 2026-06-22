<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * タグ slug の一意制約を org スコープへ是正（#464）。
 *
 * `tags.slug` はマルチテナント移行後もグローバル UNIQUE のままで、別 org が
 * 既存と同じ slug を作ると DB レベルで弾かれていた（テナント間衝突）。
 * `entity_types`（uq_entity_types_org_slug）と同様に (organization_id, slug) の
 * 複合 UNIQUE へ移行する。INFORMATION_SCHEMA で存在確認してからべき等に実行。
 */
final class ScopeTagsSlugUniqueToOrg extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->indexExists('tags', 'uq_tags_org_slug')) {
            $this->execute('ALTER TABLE tags ADD UNIQUE INDEX uq_tags_org_slug (organization_id, slug)');
        }
        if ($this->indexExists('tags', 'slug')) {
            $this->execute('ALTER TABLE tags DROP INDEX `slug`');
        }
    }

    public function down(): void
    {
        if (!$this->indexExists('tags', 'slug')) {
            $this->execute('ALTER TABLE tags ADD UNIQUE INDEX `slug` (slug)');
        }
        if ($this->indexExists('tags', 'uq_tags_org_slug')) {
            $this->execute('ALTER TABLE tags DROP INDEX uq_tags_org_slug');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $row = $this->fetchRow("
            SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND INDEX_NAME = '{$indexName}'
            LIMIT 1
        ");

        return $row !== false;
    }
}
