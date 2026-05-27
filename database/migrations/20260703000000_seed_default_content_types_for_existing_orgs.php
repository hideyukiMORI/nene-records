<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 既存の Organization に対してデフォルトコンテンツタイプ（Posts / Pages）を補完する。
 *
 * 背景:
 *   - 20260601000000_seed_default_content_types は organization_id カラム追加前に実行されたため
 *     org_id=0（番兵値）にのみデータが存在する。
 *   - 20260628000001_add_organization_id_to_all_tables でマルチテナント化した後、
 *     新規 org のデフォルトタイプが作られない問題があった。
 *
 * このマイグレーション:
 *   1. organizations テーブルに存在するすべての org_id に対して Posts / Pages を Upsert する。
 *   2. 各タイプに対して title (text) / body (markdown) の field_defs も Upsert する。
 *   3. べき等: 既存行があればスキップする。
 */
final class SeedDefaultContentTypesForExistingOrgs extends AbstractMigration
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

    /** @var list<array{name: string, slug: string, fields: list<array{field_key: string, data_type: string}>}> */
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

    public function up(): void
    {
        if (!$this->hasTable('organizations') || !$this->hasTable('entity_types') || !$this->hasTable('field_defs')) {
            return;
        }

        $pdo = $this->getAdapter()->getConnection();

        // すべての組織 ID を取得
        $orgs = $pdo->query('SELECT id FROM organizations ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($orgs as $orgId) {
            $orgId = (int) $orgId;
            $this->seedForOrg($pdo, $orgId);
        }
    }

    public function down(): void
    {
        // このマイグレーションは補完用なのでロールバックは何もしない
    }

    private function seedForOrg(PDO $pdo, int $orgId): void
    {
        foreach (self::CONTENT_TYPES as $type) {
            // entity_type が存在するか確認
            $check = $pdo->prepare('SELECT id, labels FROM entity_types WHERE organization_id = ? AND slug = ?');
            $check->execute([$orgId, $type['slug']]);
            $row = $check->fetch(PDO::FETCH_ASSOC);

            if ($row === false) {
                // 存在しない → 新規挿入
                $labels = json_encode(self::LABELS[$type['slug']] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $insert = $pdo->prepare(
                    'INSERT INTO entity_types (organization_id, name, slug, is_pinned, labels) VALUES (?, ?, ?, 1, ?)',
                );
                $insert->execute([$orgId, $type['name'], $type['slug'], $labels]);
                $entityTypeId = (int) $pdo->lastInsertId();
            } else {
                $entityTypeId = (int) $row['id'];

                // labels が NULL / 空なら補完
                if ($row['labels'] === null || $row['labels'] === '') {
                    $labels  = json_encode(self::LABELS[$type['slug']] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    $update  = $pdo->prepare('UPDATE entity_types SET labels = ? WHERE id = ?');
                    $update->execute([$labels, $entityTypeId]);
                }
            }

            // field_defs を Upsert
            foreach ($type['fields'] as $field) {
                $checkField = $pdo->prepare(
                    'SELECT id FROM field_defs WHERE organization_id = ? AND entity_type_id = ? AND field_key = ? AND is_deleted = 0',
                );
                $checkField->execute([$orgId, $entityTypeId, $field['field_key']]);

                if ($checkField->fetch() === false) {
                    $insertField = $pdo->prepare(
                        'INSERT INTO field_defs (organization_id, entity_type_id, field_key, data_type, target_entity_type_id, cardinality) VALUES (?, ?, ?, ?, NULL, NULL)',
                    );
                    $insertField->execute([$orgId, $entityTypeId, $field['field_key'], $field['data_type']]);
                }
            }
        }
    }
}
