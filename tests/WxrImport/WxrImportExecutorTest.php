<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\WxrImport;

use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityTag\InMemoryEntityTagRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use NeNeRecords\Tests\Tag\InMemoryTagRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use NeNeRecords\Tests\UrlRedirect\InMemoryUrlRedirectRepository;
use NeNeRecords\WxrImport\WxrDocument;
use NeNeRecords\WxrImport\WxrImportExecutor;
use NeNeRecords\WxrImport\WxrImportResult;
use NeNeRecords\WxrImport\WxrParser;
use PHPUnit\Framework\TestCase;

final class WxrImportExecutorTest extends TestCase
{
    private InMemoryEntityTypeRepository $entityTypes;
    private InMemoryFieldDefRepository $fieldDefs;
    private InMemoryEntityRepository $entities;
    private InMemoryTextFieldRepository $textFields;
    private InMemoryTagRepository $tags;
    private InMemoryEntityTagRepository $entityTags;
    private InMemoryUrlRedirectRepository $redirects;
    private WxrImportExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityTypes = new InMemoryEntityTypeRepository([]);
        $this->fieldDefs = new InMemoryFieldDefRepository([]);
        $this->entities = new InMemoryEntityRepository([]);
        $this->textFields = new InMemoryTextFieldRepository([], $this->entities);
        $this->tags = new InMemoryTagRepository([]);
        $this->entityTags = new InMemoryEntityTagRepository();
        $this->redirects = new InMemoryUrlRedirectRepository();
        $this->executor = new WxrImportExecutor(
            $this->entityTypes,
            $this->fieldDefs,
            $this->entities,
            $this->textFields,
            $this->tags,
            $this->entityTags,
            $this->redirects,
        );
    }

    private function document(): WxrDocument
    {
        $xml = file_get_contents(__DIR__ . '/fixtures/sample.wxr.xml');
        self::assertNotFalse($xml);

        return (new WxrParser())->parse($xml);
    }

    public function testImportsPostsPagesFieldsAndTags(): void
    {
        $result = $this->executor->execute($this->document());

        self::assertInstanceOf(WxrImportResult::class, $result);
        self::assertSame(4, $result->createdEntities); // hello, about, draft, no-slug
        self::assertSame(0, $result->skippedExisting);
        self::assertCount(1, $result->skippedItems); // attachment
        self::assertSame(2, $result->tagsEnsured);    // news, php
        self::assertSame(2, $result->tagLinks);       // hello-world ← news, php

        // Entity types were created on demand.
        $posts = $this->entityTypes->findBySlug('posts');
        $pages = $this->entityTypes->findBySlug('pages');
        self::assertNotNull($posts);
        self::assertNotNull($pages);
        self::assertNotNull($posts->id);

        // Field defs ensured: title=text, body=html.
        $titleDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($posts->id, 'title');
        $bodyDef = $this->fieldDefs->findByEntityTypeIdAndFieldKey($posts->id, 'body');
        self::assertNotNull($titleDef);
        self::assertSame('text', $titleDef->dataType);
        self::assertNotNull($bodyDef);
        self::assertSame('html', $bodyDef->dataType);

        // hello-world: published, title + body content stored.
        $hello = $this->entities->findBySlug('hello-world', $posts->id);
        self::assertNotNull($hello);
        self::assertNotNull($hello->id);
        self::assertSame(EntityStatus::Published, $hello->status);

        $values = [];
        foreach ($this->textFields->findByEntityId($hello->id, 100, 0) as $tf) {
            $values[$tf->fieldKey] = $tf->value;
        }
        self::assertSame('Hello World', $values['title'] ?? null);
        self::assertStringContainsString('<strong>world</strong>', $values['body'] ?? '');

        // Tags linked.
        $news = $this->tags->findBySlug('news');
        self::assertNotNull($news);
        self::assertNotNull($news->id);
        self::assertSame('News', $news->name); // resolved from <wp:category> definition
        self::assertTrue($this->entityTags->isAttached($hello->id, $news->id));
    }

    public function testAppliesImportedSeoMetaToEntity(): void
    {
        $this->executor->execute($this->document());

        $posts = $this->entityTypes->findBySlug('posts');
        self::assertNotNull($posts?->id);
        $hello = $this->entities->findBySlug('hello-world', $posts->id);
        self::assertNotNull($hello);

        self::assertSame('Hello World — Custom SEO Title', $hello->metaTitle);
        self::assertSame('A friendly greeting, search-optimized.', $hello->metaDescription);
    }

    public function testDraftStatusMappedAndPageImportedIntoPagesType(): void
    {
        $this->executor->execute($this->document());

        $posts = $this->entityTypes->findBySlug('posts');
        $pages = $this->entityTypes->findBySlug('pages');
        self::assertNotNull($posts?->id);
        self::assertNotNull($pages?->id);

        $draft = $this->entities->findBySlug('draft-post', $posts->id);
        self::assertNotNull($draft);
        self::assertSame(EntityStatus::Draft, $draft->status);

        $about = $this->entities->findBySlug('about', $pages->id);
        self::assertNotNull($about);
        self::assertSame(EntityStatus::Published, $about->status);
    }

    public function testIsIdempotentOnReRun(): void
    {
        $doc = $this->document();
        $this->executor->execute($doc);
        $second = $this->executor->execute($doc);

        self::assertSame(0, $second->createdEntities);
        self::assertSame(4, $second->skippedExisting);
        self::assertSame(0, $second->tagLinks); // already attached
    }

    public function testPromotesEmptyMarkdownBodyToHtml(): void
    {
        // A type seeded with a markdown `body` but no entities yet (fresh target).
        $entityTypes = new InMemoryEntityTypeRepository([new EntityType(name: 'Posts', slug: 'posts', id: 1)]);
        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'body', dataType: 'markdown', id: 1),
        ]);
        $entities = new InMemoryEntityRepository([]);
        $textFields = new InMemoryTextFieldRepository([], $entities);
        $executor = new WxrImportExecutor(
            $entityTypes,
            $fieldDefs,
            $entities,
            $textFields,
            new InMemoryTagRepository([]),
            new InMemoryEntityTagRepository(),
            new InMemoryUrlRedirectRepository(),
        );

        $result = $executor->execute($this->document());

        // body promoted markdown → html (safe: no values existed).
        self::assertSame('html', $fieldDefs->findByEntityTypeIdAndFieldKey(1, 'body')?->dataType);
        // content written to body and faithful.
        $hello = $entities->findBySlug('hello-world', 1);
        self::assertNotNull($hello?->id);
        self::assertStringContainsString('<strong>world</strong>', $this->bodyValue($textFields, $hello->id, 'body'));
        self::assertNotEmpty(array_filter($result->warnings, static fn (string $w): bool => str_contains($w, '昇格')));
    }

    public function testWritesToDedicatedFieldWhenTypeHasNativeContent(): void
    {
        // A type with a markdown `body` that already holds native content.
        $entityTypes = new InMemoryEntityTypeRepository([new EntityType(name: 'Posts', slug: 'posts', id: 1)]);
        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'body', dataType: 'markdown', id: 1),
        ]);
        $entities = new InMemoryEntityRepository([
            new Entity(id: 5, entityTypeId: 1, slug: 'native-post', status: EntityStatus::Published),
        ]);
        $textFields = new InMemoryTextFieldRepository([], $entities);
        $executor = new WxrImportExecutor(
            $entityTypes,
            $fieldDefs,
            $entities,
            $textFields,
            new InMemoryTagRepository([]),
            new InMemoryEntityTagRepository(),
            new InMemoryUrlRedirectRepository(),
        );

        $result = $executor->execute($this->document());

        // Existing markdown body left intact; import lands in a dedicated html field.
        self::assertSame('markdown', $fieldDefs->findByEntityTypeIdAndFieldKey(1, 'body')?->dataType);
        self::assertSame('html', $fieldDefs->findByEntityTypeIdAndFieldKey(1, 'wp_content')?->dataType);
        $hello = $entities->findBySlug('hello-world', 1);
        self::assertNotNull($hello?->id);
        self::assertStringContainsString('<strong>world</strong>', $this->bodyValue($textFields, $hello->id, 'wp_content'));
        self::assertSame('', $this->bodyValue($textFields, $hello->id, 'body'));
        self::assertNotEmpty(array_filter($result->warnings, static fn (string $w): bool => str_contains($w, 'wp_content')));
    }

    private function bodyValue(InMemoryTextFieldRepository $textFields, int $entityId, string $fieldKey): string
    {
        foreach ($textFields->findByEntityId($entityId, 100, 0) as $tf) {
            if ($tf->fieldKey === $fieldKey) {
                return $tf->value;
            }
        }

        return '';
    }

    public function testRewritesBodyImageUrlsFromMediaMap(): void
    {
        $oldUrl = 'https://old.example.com/wp-content/uploads/2024/01/image.jpg';
        $this->executor->execute($this->document(), [$oldUrl => '/media/imported/image.jpg']);

        $posts = $this->entityTypes->findBySlug('posts');
        self::assertNotNull($posts?->id);
        $hello = $this->entities->findBySlug('hello-world', $posts->id);
        self::assertNotNull($hello?->id);

        $body = '';
        foreach ($this->textFields->findByEntityId($hello->id, 100, 0) as $tf) {
            if ($tf->fieldKey === 'body') {
                $body = $tf->value;
            }
        }

        self::assertStringContainsString('/media/imported/image.jpg', $body);
        self::assertStringNotContainsString('old.example.com', $body);
    }

    public function testRecordsRedirectMapFromOriginalUrls(): void
    {
        $result = $this->executor->execute($this->document());

        // hello-world + about carry <link>; draft/no-slug do not; attachment is skipped.
        self::assertSame(2, $result->redirectsCreated);

        $posts = $this->entityTypes->findBySlug('posts');
        self::assertNotNull($posts?->id);
        $hello = $this->entities->findBySlug('hello-world', $posts->id);
        self::assertNotNull($hello?->id);

        $map = $this->redirects->all();
        // old WP URL path (trailing slash stripped) → new permalink (default /{type}/{id})
        self::assertArrayHasKey('/2024/01/hello-world', $map);
        self::assertSame('/posts/' . (string) $hello->id, $map['/2024/01/hello-world']);
        self::assertArrayHasKey('/about', $map);
    }
}
