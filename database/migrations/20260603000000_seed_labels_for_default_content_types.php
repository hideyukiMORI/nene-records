<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * デフォルトコンテンツタイプ（posts / pages）に多言語ラベルを追加する。
 *
 * 依存: 20260602000000_add_labels_to_entity_types（labels カラム追加済み）
 */
final class SeedLabelsForDefaultContentTypes extends AbstractMigration
{
    /** @var array<string, array<string, string>> slug => locale => label */
    private const LABELS = [
        'posts' => [
            'ja'      => '投稿',
            'fr'      => 'Articles',
            'zh-Hans' => '文章',
            'pt-BR'   => 'Publicações',
            'de'      => 'Beiträge',
        ],
        'pages' => [
            'ja'      => '固定ページ',
            'fr'      => 'Pages',
            'zh-Hans' => '页面',
            'pt-BR'   => 'Páginas',
            'de'      => 'Seiten',
        ],
    ];

    public function up(): void
    {
        if (!$this->hasTable('entity_types')) {
            return;
        }

        $pdo = $this->getAdapter()->getConnection();
        $update = $pdo->prepare('UPDATE entity_types SET labels = ? WHERE slug = ?');

        foreach (self::LABELS as $slug => $labels) {
            $update->execute([
                json_encode($labels, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                $slug,
            ]);
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('entity_types')) {
            return;
        }

        $pdo = $this->getAdapter()->getConnection();
        $update = $pdo->prepare('UPDATE entity_types SET labels = NULL WHERE slug = ?');

        foreach (array_keys(self::LABELS) as $slug) {
            $update->execute([$slug]);
        }
    }
}
