<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Seeds the two built-in entity types — Posts and Pages — together with their
 * default field definitions for a newly created organization.
 *
 * SQL lives here (Pdo* class) to keep it out of the UseCase layer.
 * The implementation is idempotent: it checks for existing rows before inserting.
 */
final readonly class PdoDefaultContentTypeSeeder implements DefaultContentTypeSeederInterface
{
    /** @var array<string, array<string, string>> */
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

    /**
     * @var list<array{name: string, slug: string, fields: list<array{field_key: string, data_type: string}>}>
     */
    private const CONTENT_TYPES = [
        [
            'name'   => 'Posts',
            'slug'   => 'posts',
            'fields' => [
                ['field_key' => 'title', 'data_type' => 'text'],
                ['field_key' => 'body',  'data_type' => 'markdown'],
            ],
        ],
        [
            'name'   => 'Pages',
            'slug'   => 'pages',
            'fields' => [
                ['field_key' => 'title', 'data_type' => 'text'],
                ['field_key' => 'body',  'data_type' => 'markdown'],
            ],
        ],
    ];

    public function __construct(private DatabaseQueryExecutorInterface $query)
    {
    }

    public function seed(int $organizationId): void
    {
        foreach (self::CONTENT_TYPES as $type) {
            $entityTypeId = $this->upsertEntityType($organizationId, $type['name'], $type['slug']);

            foreach ($type['fields'] as $field) {
                $this->upsertFieldDef($organizationId, $entityTypeId, $field['field_key'], $field['data_type']);
            }
        }
    }

    /**
     * Insert entity_type if it does not already exist for this org.
     * Returns the id of the existing or newly inserted row.
     */
    private function upsertEntityType(int $organizationId, string $name, string $slug): int
    {
        $existing = $this->query->fetchOne(
            'SELECT id FROM entity_types WHERE organization_id = ? AND slug = ?',
            [$organizationId, $slug],
        );

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $labels = json_encode(self::LABELS[$slug] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->query->execute(
            'INSERT INTO entity_types (organization_id, name, slug, is_pinned, labels) VALUES (?, ?, ?, 1, ?)',
            [$organizationId, $name, $slug, $labels],
        );

        return $this->query->lastInsertId();
    }

    /**
     * Insert field_def if it does not already exist (active) for this entity type + field key.
     */
    private function upsertFieldDef(
        int $organizationId,
        int $entityTypeId,
        string $fieldKey,
        string $dataType,
    ): void {
        $existing = $this->query->fetchOne(
            'SELECT id FROM field_defs WHERE organization_id = ? AND entity_type_id = ? AND field_key = ? AND is_deleted = 0',
            [$organizationId, $entityTypeId, $fieldKey],
        );

        if ($existing !== null) {
            return;
        }

        $this->query->execute(
            'INSERT INTO field_defs (organization_id, entity_type_id, field_key, data_type, target_entity_type_id, cardinality) VALUES (?, ?, ?, ?, NULL, NULL)',
            [$organizationId, $entityTypeId, $fieldKey, $dataType],
        );
    }
}
