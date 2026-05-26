<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SeedDefaultContentTypes extends AbstractMigration
{
    private const TYPES = [
        [
            'name' => 'Posts',
            'slug' => 'posts',
            'is_pinned' => 1,
            'fields' => [
                ['field_key' => 'title', 'data_type' => 'text'],
                ['field_key' => 'body', 'data_type' => 'text'],
            ],
        ],
        [
            'name' => 'Pages',
            'slug' => 'pages',
            'is_pinned' => 1,
            'fields' => [
                ['field_key' => 'title', 'data_type' => 'text'],
                ['field_key' => 'body', 'data_type' => 'text'],
            ],
        ],
    ];

    public function up(): void
    {
        if (!$this->hasTable('entity_types') || !$this->hasTable('field_defs')) {
            return;
        }

        $pdo = $this->getAdapter()->getConnection();

        foreach (self::TYPES as $type) {
            // Skip if slug already exists
            $check = $pdo->prepare('SELECT id FROM entity_types WHERE slug = ?');
            $check->execute([$type['slug']]);

            if ($check->fetch() !== false) {
                continue;
            }

            $this->table('entity_types')->insert([
                'name' => $type['name'],
                'slug' => $type['slug'],
                'is_pinned' => $type['is_pinned'],
            ])->saveData();

            $entityTypeId = (int) $pdo->lastInsertId();

            foreach ($type['fields'] as $field) {
                $this->table('field_defs')->insert([
                    'entity_type_id' => $entityTypeId,
                    'field_key' => $field['field_key'],
                    'data_type' => $field['data_type'],
                    'target_entity_type_id' => null,
                    'cardinality' => null,
                    'is_deleted' => 0,
                    'deleted_at' => null,
                ])->saveData();
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('entity_types') || !$this->hasTable('field_defs')) {
            return;
        }

        $pdo = $this->getAdapter()->getConnection();

        foreach (self::TYPES as $type) {
            $row = $pdo->query(
                "SELECT id FROM entity_types WHERE slug = '" . $type['slug'] . "'",
            )->fetch();

            if ($row === false) {
                continue;
            }

            $id = (int) $row['id'];
            $this->execute("DELETE FROM field_defs WHERE entity_type_id = {$id}");
            $this->execute("DELETE FROM entity_types WHERE id = {$id}");
        }
    }
}
