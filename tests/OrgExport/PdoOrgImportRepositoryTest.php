<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\OrgExport;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Database\PdoDatabaseTransactionManager;
use NeNeRecords\OrgExport\PdoOrgImportRepository;
use NeNeRecords\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/**
 * Import repository behaviour for cross-instance transport (#741):
 *  - seeded rows (entity_types slug / field_defs / setting_defs) are MERGED, not duplicated
 *  - id remap follows onto downstream FKs
 *  - the whole import is transactional (a mid-way failure leaves the org untouched)
 *  - INSERT column lists track the live schema (permalink / menu_order / layout,
 *    media.alt_text/width/height/storage_key, navigation_items.menu_id)
 *
 * A file-backed SQLite DB is used (not :memory:) so the transaction manager's own
 * connection shares state with the setup connection.
 */
final class PdoOrgImportRepositoryTest extends TestCase
{
    private string $dbPath;
    private PdoDatabaseQueryExecutor $executor;
    private PdoConnectionFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = tempnam(sys_get_temp_dir(), 'nene-import-') ?: throw new \RuntimeException('tempnam failed');

        $this->factory  = new PdoConnectionFactory(new DatabaseConfig(
            null,
            'test',
            'sqlite',
            'localhost',
            1,
            $this->dbPath,
            'nene-records-test',
            '',
            'utf8',
        ));
        $this->executor = new PdoDatabaseQueryExecutor($this->factory);
        $this->executor->execute('PRAGMA foreign_keys = ON');

        foreach ($this->schemaStatements() as $statement) {
            $this->executor->execute($statement);
        }

        $this->seedFreshOrg(1);
    }

    protected function tearDown(): void
    {
        @unlink($this->dbPath);
        parent::tearDown();
    }

    public function testMergesSeededRowsAndPreservesNewColumns(): void
    {
        $repository = new PdoOrgImportRepository(
            new PdoDatabaseTransactionManager($this->factory),
            new FixedClock(),
        );

        $counts = $repository->import(1, $this->transportPayload());

        // entity_types: seeded "posts" merged (not duplicated) + new "books" → 2 rows total.
        $types = $this->executor->fetchAll('SELECT slug FROM entity_types WHERE organization_id = 1 ORDER BY slug');
        self::assertSame(['books', 'posts'], array_map(static fn ($r) => $r['slug'], $types));

        // field_defs: seeded title/body on posts stay single (merged), new field on books added.
        $postFieldCount = $this->executor->fetchOne(
            "SELECT COUNT(*) c FROM field_defs f
              JOIN entity_types t ON t.id = f.entity_type_id
              WHERE t.slug = 'posts' AND f.is_deleted = 0",
        );
        self::assertSame(2, (int) ($postFieldCount['c'] ?? 0));

        // entity carries the new columns (permalink / menu_order / layout).
        $entity = $this->executor->fetchOne("SELECT * FROM entities WHERE slug = 'hello-world'");
        self::assertNotNull($entity);
        self::assertSame('/blog/hello-world', $entity['permalink']);
        self::assertSame(7, (int) $entity['menu_order']);
        self::assertSame('wide', $entity['layout']);

        // text field remapped onto the new entity id.
        $body = $this->executor->fetchOne(
            'SELECT value FROM text_fields WHERE entity_id = ? AND field_key = ?',
            [(int) $entity['id'], 'body'],
        );
        self::assertSame('Hello body', $body['value'] ?? null);

        // media carries alt_text / width / height / storage_key.
        $media = $this->executor->fetchOne("SELECT * FROM media WHERE stored_name = 'abc.png'");
        self::assertNotNull($media);
        self::assertSame('A logo', $media['alt_text']);
        self::assertSame(640, (int) $media['width']);
        self::assertSame(480, (int) $media['height']);
        self::assertSame('media/2026/abc.png', $media['storage_key']);

        // navigation_items keeps the menu_id column but leaves it NULL in Phase 1.
        $nav = $this->executor->fetchOne("SELECT * FROM navigation_items WHERE label = 'Home'");
        self::assertNotNull($nav);
        self::assertNull($nav['menu_id']);

        // setting_defs merged on setting_key (source-wins), not duplicated.
        $siteName = $this->executor->fetchAll("SELECT default_value FROM setting_defs WHERE organization_id = 1 AND setting_key = 'site_name'");
        self::assertCount(1, $siteName);
        self::assertSame('Imported Site', $siteName[0]['default_value']);

        // setting_values merged on setting_key.
        $value = $this->executor->fetchOne("SELECT value FROM setting_values WHERE organization_id = 1 AND setting_key = 'site_name'");
        self::assertSame('My Blog', $value['value'] ?? null);

        self::assertSame(2, $counts['entity_types']);
    }

    public function testImportsPhase2TablesWithIdRemap(): void
    {
        $repository = new PdoOrgImportRepository(
            new PdoDatabaseTransactionManager($this->factory),
            new FixedClock(),
        );

        $counts = $repository->import(1, $this->phase2Payload());

        // menu imported → its id is used by both a navigation item and a menu widget.
        $menu = $this->executor->fetchOne("SELECT id FROM menus WHERE organization_id = 1 AND slug = 'main'");
        self::assertNotNull($menu);
        $newMenuId = (int) $menu['id'];

        // navigation_items.menu_id remapped onto the imported menu (not the source id 8).
        $nav = $this->executor->fetchOne("SELECT menu_id FROM navigation_items WHERE label = 'Blog'");
        self::assertNotNull($nav);
        self::assertSame($newMenuId, (int) $nav['menu_id']);

        // menu widget settings.menuId remapped.
        $widget = $this->executor->fetchOne("SELECT settings FROM widgets WHERE widget_type = 'menu'");
        self::assertNotNull($widget);
        $settings = json_decode((string) $widget['settings'], true);
        self::assertIsArray($settings);
        self::assertSame($newMenuId, $settings['menuId']);

        // theme imported.
        $theme = $this->executor->fetchOne("SELECT manifest FROM themes WHERE organization_id = 1 AND theme_key = 'custom'");
        self::assertNotNull($theme);
        self::assertStringContainsString('brand', (string) $theme['manifest']);

        // blocks_fields body preserved and remapped onto the new entity id.
        $entity = $this->executor->fetchOne("SELECT id FROM entities WHERE slug = 'hello-world'");
        self::assertNotNull($entity);
        $block  = $this->executor->fetchOne(
            'SELECT value FROM blocks_fields WHERE entity_id = ? AND field_key = ?',
            [(int) $entity['id'], 'blocks'],
        );
        self::assertNotNull($block);
        self::assertStringContainsString('paragraph', (string) $block['value']);

        // entity_relations remapped onto the two new entity ids.
        $other = $this->executor->fetchOne("SELECT id FROM entities WHERE slug = 'second'");
        self::assertNotNull($other);
        $relation = $this->executor->fetchOne(
            'SELECT field_key FROM entity_relations WHERE source_entity_id = ? AND target_entity_id = ?',
            [(int) $entity['id'], (int) $other['id']],
        );
        self::assertNotNull($relation);
        self::assertSame('related', $relation['field_key']);

        // url_redirect imported.
        $redirect = $this->executor->fetchOne("SELECT target_path FROM url_redirects WHERE organization_id = 1 AND source_path = '/old'");
        self::assertNotNull($redirect);
        self::assertSame('/new', $redirect['target_path']);

        // logo_media_id setting value remapped onto the imported media row.
        $media = $this->executor->fetchOne("SELECT id FROM media WHERE stored_name = 'logo.png'");
        self::assertNotNull($media);
        $logo = $this->executor->fetchOne("SELECT value FROM setting_values WHERE organization_id = 1 AND setting_key = 'logo_media_id'");
        self::assertNotNull($logo);
        self::assertSame((string) (int) $media['id'], $logo['value']);

        // front_page setting value remapped onto the imported entity row (#801) —
        // it pinned source id 30, which must now point at the new hello-world id.
        $frontPage = $this->executor->fetchOne("SELECT value FROM setting_values WHERE organization_id = 1 AND setting_key = 'front_page'");
        self::assertNotNull($frontPage);
        self::assertSame((string) (int) $entity['id'], $frontPage['value']);

        // #795: the hero block's media reference was remapped onto the new media id
        // and its absolute URL relativized — both in the blocks_fields body and in
        // the home_hero setting (same block-document shape).
        $newMediaId = (string) (int) $media['id'];
        $blockMedia = json_decode((string) $block['value'], true);
        self::assertSame($newMediaId, $blockMedia[1]['data']['media']['mediaId']);
        self::assertSame('/media/logo.png', $blockMedia[1]['data']['media']['url']);

        $homeHero = $this->executor->fetchOne("SELECT value FROM setting_values WHERE organization_id = 1 AND setting_key = 'home_hero'");
        self::assertNotNull($homeHero);
        $hero = json_decode((string) $homeHero['value'], true);
        self::assertSame($newMediaId, $hero[0]['data']['media']['mediaId']);
        self::assertSame('/media/logo.png', $hero[0]['data']['media']['url']);

        self::assertSame(1, $counts['menus']);
        self::assertSame(1, $counts['widgets']);
        self::assertSame(1, $counts['entity_relations']);
    }

    public function testImportsPhase3TablesWithIdRemapAndSecretScrub(): void
    {
        $repository = new PdoOrgImportRepository(
            new PdoDatabaseTransactionManager($this->factory),
            new FixedClock(),
        );

        $counts = $repository->import(1, $this->phase3Payload());

        $entity   = $this->executor->fetchOne("SELECT id FROM entities WHERE slug = 'hello-world'");
        self::assertNotNull($entity);
        $newEntityId = (int) $entity['id'];
        $postsType   = $this->executor->fetchOne("SELECT id FROM entity_types WHERE organization_id = 1 AND slug = 'posts'");
        self::assertNotNull($postsType);
        $newTypeId = (int) $postsType['id'];

        // comments: valid one imported + org-stamped; the one for a missing entity skipped.
        $comment = $this->executor->fetchOne("SELECT * FROM comments WHERE author_email = 'jane@example.test'");
        self::assertNotNull($comment);
        self::assertSame($newEntityId, (int) $comment['entity_id']);
        self::assertSame(1, (int) $comment['organization_id']);
        self::assertSame('Nice post', $comment['body']);
        self::assertSame(1, $counts['comments']);

        // webhooks: secret dropped (NULL), entity_type_id remapped, org-stamped.
        $webhook = $this->executor->fetchOne('SELECT * FROM webhooks WHERE organization_id = 1');
        self::assertNotNull($webhook);
        self::assertNull($webhook['secret']);
        self::assertSame($newTypeId, (int) $webhook['entity_type_id']);
        $newWebhookId = (int) $webhook['id'];

        // webhook_deliveries: parent + entity remapped, secret dropped; orphan skipped.
        $delivery = $this->executor->fetchOne("SELECT * FROM webhook_deliveries WHERE event = 'entity.created'");
        self::assertNotNull($delivery);
        self::assertNull($delivery['secret']);
        self::assertSame($newWebhookId, (int) $delivery['webhook_id']);
        self::assertSame($newEntityId, (int) $delivery['entity_id']);
        self::assertSame($newTypeId, (int) $delivery['entity_type_id']);
        self::assertSame(1, $counts['webhook_deliveries']);

        // notification_channels: org-stamped, config preserved.
        $channel = $this->executor->fetchOne('SELECT * FROM notification_channels WHERE organization_id = 1');
        self::assertNotNull($channel);
        self::assertSame('slack', $channel['channel_type']);
        self::assertStringContainsString('hooks.slack', (string) $channel['config_json']);

        // user_profiles: attached to the same-email target user; the ghost user is skipped + reported.
        $targetUser = $this->executor->fetchOne("SELECT id FROM users WHERE email = 'admin@target.test'");
        self::assertNotNull($targetUser);
        $profile = $this->executor->fetchOne('SELECT * FROM user_profiles WHERE user_id = ?', [(int) $targetUser['id']]);
        self::assertNotNull($profile);
        self::assertSame('Rina', $profile['display_name']);
        self::assertSame(1, $counts['user_profiles']);
        self::assertSame(1, $counts['user_profiles_skipped']);
    }

    public function testFailedImportRollsBackEntireOrg(): void
    {
        $repository = new PdoOrgImportRepository(
            new PdoDatabaseTransactionManager($this->factory),
            new FixedClock(),
        );

        // Two entities share the same permalink → the second INSERT violates the
        // unique (organization_id, permalink) index mid-transaction.
        $payload = [
            'meta'         => ['organization_id' => 1],
            'entity_types' => [['id' => 90, 'name' => 'Books', 'slug' => 'books', 'is_pinned' => 0]],
            'entities'     => [
                ['id' => 500, 'entity_type_id' => 90, 'slug' => 'a', 'permalink' => '/dup', 'status' => 'published'],
                ['id' => 501, 'entity_type_id' => 90, 'slug' => 'b', 'permalink' => '/dup', 'status' => 'published'],
            ],
        ];

        try {
            $repository->import(1, $payload);
            self::fail('Expected the duplicate permalink to abort the import.');
        } catch (\Throwable) {
            // expected
        }

        // Nothing from this import survived: no "books" type, no entities.
        $books = $this->executor->fetchAll("SELECT id FROM entity_types WHERE organization_id = 1 AND slug = 'books'");
        self::assertSame([], $books);
        $entities = $this->executor->fetchAll('SELECT id FROM entities WHERE organization_id = 1');
        self::assertSame([], $entities);
    }

    /** @return array<string, mixed> */
    private function transportPayload(): array
    {
        return [
            'meta'             => ['organization_id' => 42],
            'entity_types'     => [
                ['id' => 10, 'name' => 'Posts', 'slug' => 'posts', 'is_pinned' => 1, 'default_layout' => 'standard', 'display_order' => 0],
                ['id' => 11, 'name' => 'Books', 'slug' => 'books', 'is_pinned' => 0, 'default_layout' => 'standard', 'display_order' => 1],
            ],
            'field_defs'       => [
                ['id' => 20, 'entity_type_id' => 10, 'field_key' => 'title', 'data_type' => 'text', 'is_deleted' => 0],
                ['id' => 21, 'entity_type_id' => 10, 'field_key' => 'body', 'data_type' => 'markdown', 'is_deleted' => 0],
                ['id' => 22, 'entity_type_id' => 11, 'field_key' => 'isbn', 'data_type' => 'text', 'is_deleted' => 0],
            ],
            'entities'         => [
                [
                    'id' => 30, 'entity_type_id' => 10, 'slug' => 'hello-world', 'permalink' => '/blog/hello-world',
                    'menu_order' => 7, 'layout' => 'wide', 'status' => 'published',
                ],
            ],
            'text_fields'      => [
                ['id' => 40, 'entity_id' => 30, 'field_key' => 'body', 'value' => 'Hello body', 'is_deleted' => 0],
            ],
            'tags'             => [['id' => 50, 'slug' => 'news', 'name' => 'News']],
            'entity_tags'      => [['entity_id' => 30, 'tag_id' => 50]],
            'media'            => [
                [
                    'id' => 60, 'original_name' => 'logo.png', 'stored_name' => 'abc.png', 'mime_type' => 'image/png',
                    'alt_text' => 'A logo', 'size' => 1234, 'width' => 640, 'height' => 480,
                    'url' => '/media/abc.png', 'storage_key' => 'media/2026/abc.png',
                ],
            ],
            'navigation_items' => [
                ['id' => 70, 'menu_id' => 3, 'label' => 'Home', 'url' => '/', 'display_order' => 0],
            ],
            'setting_defs'     => [
                ['id' => 80, 'setting_key' => 'site_name', 'data_type' => 'text', 'default_value' => 'Imported Site', 'is_public' => 1, 'label' => 'Site name'],
            ],
            'setting_values'   => [
                ['id' => 90, 'setting_key' => 'site_name', 'value' => 'My Blog', 'is_deleted' => 0],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function phase2Payload(): array
    {
        return [
            'meta'             => ['organization_id' => 99],
            'entity_types'     => [['id' => 10, 'name' => 'Posts', 'slug' => 'posts', 'is_pinned' => 1]],
            'entities'         => [
                ['id' => 30, 'entity_type_id' => 10, 'slug' => 'hello-world', 'status' => 'published'],
                ['id' => 31, 'entity_type_id' => 10, 'slug' => 'second', 'status' => 'published'],
            ],
            'media'            => [
                ['id' => 60, 'original_name' => 'logo.png', 'stored_name' => 'logo.png', 'mime_type' => 'image/png', 'size' => 10, 'url' => '/media/logo.png', 'storage_key' => 'media/logo.png'],
            ],
            'menus'            => [['id' => 8, 'name' => 'Main', 'slug' => 'main', 'location' => 'header']],
            'navigation_items' => [['id' => 70, 'menu_id' => 8, 'label' => 'Blog', 'url' => '/blog', 'display_order' => 0]],
            'widgets'          => [
                ['id' => 80, 'widget_type' => 'menu', 'region' => 'header', 'display_order' => 0, 'title' => 'Nav', 'settings' => '{"menuId":8,"layout":"inline"}'],
            ],
            'themes'           => [
                ['id' => 90, 'theme_key' => 'custom', 'name' => 'Custom', 'version' => '1.0.0', 'source' => 'runtime', 'manifest' => '{"brand":"#ff0000"}'],
            ],
            'blocks_fields'    => [
                // A paragraph (no media, preserved verbatim) plus a hero block whose
                // media references source id 60 via an absolute same-origin URL — the
                // mediaId must be remapped and the URL relativized on import (#795).
                ['id' => 100, 'entity_id' => 30, 'field_key' => 'blocks', 'value' => '[{"type":"paragraph","text":"hi"},{"id":"h","type":"hero","data":{"media":{"mediaId":"60","url":"https://source.example/media/logo.png"}}}]', 'is_deleted' => 0],
            ],
            'entity_relations' => [
                ['id' => 110, 'source_entity_id' => 30, 'target_entity_id' => 31, 'field_key' => 'related'],
            ],
            'url_redirects'    => [['id' => 120, 'source_path' => '/old', 'target_path' => '/new']],
            'setting_defs'     => [
                ['id' => 130, 'setting_key' => 'logo_media_id', 'data_type' => 'media', 'default_value' => '', 'is_public' => 1, 'label' => 'Logo'],
                ['id' => 131, 'setting_key' => 'front_page', 'data_type' => 'int', 'default_value' => '', 'is_public' => 1, 'label' => 'Front page'],
                ['id' => 132, 'setting_key' => 'home_hero', 'data_type' => 'text', 'default_value' => '[]', 'is_public' => 1, 'label' => 'Home hero'],
            ],
            'setting_values'   => [
                ['id' => 140, 'setting_key' => 'logo_media_id', 'value' => '60', 'is_deleted' => 0],
                // front_page pins source entity 30 → must be remapped onto the new id.
                ['id' => 141, 'setting_key' => 'front_page', 'value' => '30', 'is_deleted' => 0],
                // home_hero is a blocks document → its hero media (id 60) is remapped
                // + the absolute URL relativized on import (#795).
                ['id' => 142, 'setting_key' => 'home_hero', 'value' => '[{"id":"hh","type":"hero","data":{"media":{"mediaId":"60","url":"https://source.example/media/logo.png"}}}]', 'is_deleted' => 0],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function phase3Payload(): array
    {
        return [
            'meta'         => ['organization_id' => 77],
            'entity_types' => [['id' => 10, 'name' => 'Posts', 'slug' => 'posts', 'is_pinned' => 1]],
            'entities'     => [
                ['id' => 30, 'entity_type_id' => 10, 'slug' => 'hello-world', 'status' => 'published'],
            ],
            'comments'     => [
                ['id' => 200, 'entity_id' => 30, 'author_name' => 'Jane', 'author_email' => 'jane@example.test', 'body' => 'Nice post', 'is_approved' => 1, 'created_at' => '2026-05-01 00:00:00'],
                // references a missing entity → skipped.
                ['id' => 201, 'entity_id' => 999, 'author_name' => 'Ghost', 'author_email' => 'ghost@example.test', 'body' => 'orphan', 'is_approved' => 0],
            ],
            'webhooks'     => [
                // secret is present in the source row but must be dropped on import.
                ['id' => 300, 'url' => 'https://hook.test/x', 'events' => '["entity.created"]', 'entity_type_id' => 10, 'secret' => 'top-secret', 'is_active' => 1],
            ],
            'webhook_deliveries' => [
                ['id' => 400, 'webhook_id' => 300, 'event' => 'entity.created', 'entity_type_id' => 10, 'entity_id' => 30, 'target_url' => 'https://hook.test/x', 'secret' => 'top-secret', 'payload' => '{}', 'status' => 'pending', 'attempts' => 0, 'max_attempts' => 5, 'next_attempt_at' => '2026-05-01 00:00:00', 'created_at' => '2026-05-01 00:00:00', 'updated_at' => '2026-05-01 00:00:00'],
                // parent webhook missing → skipped.
                ['id' => 401, 'webhook_id' => 999, 'event' => 'entity.updated', 'entity_type_id' => 10, 'entity_id' => 30, 'target_url' => 'https://hook.test/y', 'payload' => '{}', 'status' => 'failed', 'attempts' => 5, 'max_attempts' => 5, 'next_attempt_at' => '2026-05-01 00:00:00', 'created_at' => '2026-05-01 00:00:00', 'updated_at' => '2026-05-01 00:00:00'],
            ],
            'notification_channels' => [
                ['id' => 500, 'channel_type' => 'slack', 'label' => 'Ops', 'is_enabled' => 1, 'config_json' => '{"webhook_url":"https://hooks.slack.test/abc"}', 'created_at' => '2026-05-01 00:00:00', 'updated_at' => '2026-05-01 00:00:00'],
            ],
            'user_profiles' => [
                // same email as the seeded target admin → re-attached.
                ['id' => 600, 'user_id' => 42, 'user_email' => 'admin@target.test', 'display_name' => 'Rina', 'full_name' => 'Rina R.', 'job_title' => 'Engineer', 'created_at' => '2026-05-01 00:00:00', 'updated_at' => '2026-05-01 00:00:00'],
                // no such user on the target → skipped + reported.
                ['id' => 601, 'user_id' => 43, 'user_email' => 'ghost@source.test', 'display_name' => 'Ghost'],
            ],
        ];
    }

    private function seedFreshOrg(int $orgId): void
    {
        // Mirror the fresh-install seed: posts type + title/body field_defs, and one setting_def.
        $postsId = $this->executor->insert(
            "INSERT INTO entity_types (organization_id, name, slug, is_pinned) VALUES (?, 'Posts', 'posts', 1)",
            [$orgId],
        );
        $this->executor->execute(
            "INSERT INTO field_defs (organization_id, entity_type_id, field_key, data_type) VALUES (?, ?, 'title', 'text')",
            [$orgId, $postsId],
        );
        $this->executor->execute(
            "INSERT INTO field_defs (organization_id, entity_type_id, field_key, data_type) VALUES (?, ?, 'body', 'markdown')",
            [$orgId, $postsId],
        );
        $this->executor->execute(
            "INSERT INTO setting_defs (organization_id, setting_key, data_type, default_value, is_public, label, created_at, updated_at)
             VALUES (?, 'site_name', 'text', 'NeNe Records', 1, 'Site name', '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
            [$orgId],
        );
        // A target-org admin so an imported user_profile can be re-attached by email (#796).
        $this->executor->execute(
            "INSERT INTO users (email, password_hash, role, organization_id, org_role, created_at, updated_at)
             VALUES ('admin@target.test', 'x', 'admin', ?, 'admin', '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
            [$orgId],
        );
    }

    /** @return list<string> */
    private function schemaStatements(): array
    {
        $projectRoot = dirname(__DIR__, 2);
        $paths = [
            $projectRoot . '/database/schema/entity_types.sql',
            $projectRoot . '/database/schema/field_defs.sql',
            $projectRoot . '/database/schema/entities.sql',
            $projectRoot . '/database/schema/text_fields.sql',
            $projectRoot . '/database/schema/int_fields.sql',
            $projectRoot . '/database/schema/enum_fields.sql',
            $projectRoot . '/database/schema/bool_fields.sql',
            $projectRoot . '/database/schema/datetime_fields.sql',
            $projectRoot . '/database/schema/tags.sql',
            $projectRoot . '/database/schema/entity_tags.sql',
            $projectRoot . '/database/schema/media.sql',
            $projectRoot . '/database/schema/menus.sql',
            $projectRoot . '/database/schema/navigation_items.sql',
            $projectRoot . '/database/schema/widgets.sql',
            $projectRoot . '/database/schema/themes.sql',
            $projectRoot . '/database/schema/blocks_fields.sql',
            $projectRoot . '/database/schema/entity_relations.sql',
            $projectRoot . '/database/schema/url_redirects.sql',
            $projectRoot . '/database/schema/settings.sql',
            $projectRoot . '/database/schema/comments.sql',
            $projectRoot . '/database/schema/webhooks.sql',
            $projectRoot . '/database/schema/webhook_deliveries.sql',
            $projectRoot . '/database/schema/notification_channels.sql',
            $projectRoot . '/database/schema/user_profiles.sql',
        ];

        // users.sql uses MySQL ENUM syntax SQLite cannot parse; user_profiles only
        // needs id/email/organization_id here, so a minimal SQLite users table is
        // created first (before user_profiles.sql, which carries the FK).
        $statements = [
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL DEFAULT \'\',
                role VARCHAR(32) NOT NULL DEFAULT \'admin\',
                organization_id INTEGER NULL DEFAULT NULL,
                org_role VARCHAR(32) NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT \'2026-01-01 00:00:00\',
                updated_at DATETIME NOT NULL DEFAULT \'2026-01-01 00:00:00\'
            )',
            'CREATE UNIQUE INDEX users_email ON users (email)',
        ];
        foreach ($paths as $path) {
            self::assertFileExists($path);
            $raw = trim((string) file_get_contents($path));
            foreach (preg_split('/;\R/s', $raw) ?: [] as $chunk) {
                $statement = trim($chunk);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
            }
        }

        return $statements;
    }
}
