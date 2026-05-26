<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChangeBodyToMarkdownForBlogPostsPages extends AbstractMigration
{
    public function up(): void
    {
        // blog / posts / pages エンティティタイプの body フィールドを
        // text → markdown 型に変更する
        // entity_type_id: blog=2, posts=5, pages=6 (デフォルトシード値)
        $this->execute(
            "UPDATE field_defs
             SET data_type = 'markdown'
             WHERE field_key = 'body'
               AND data_type = 'text'
               AND entity_type_id IN (
                   SELECT id FROM entity_types WHERE slug IN ('blog', 'posts', 'pages')
               )"
        );
    }

    public function down(): void
    {
        $this->execute(
            "UPDATE field_defs
             SET data_type = 'text'
             WHERE field_key = 'body'
               AND data_type = 'markdown'
               AND entity_type_id IN (
                   SELECT id FROM entity_types WHERE slug IN ('blog', 'posts', 'pages')
               )"
        );
    }
}
