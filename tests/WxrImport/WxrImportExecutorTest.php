<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\WxrImport;

use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityTag\InMemoryEntityTagRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use NeNeRecords\Tests\Tag\InMemoryTagRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
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
        $this->executor = new WxrImportExecutor(
            $this->entityTypes,
            $this->fieldDefs,
            $this->entities,
            $this->textFields,
            $this->tags,
            $this->entityTags,
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
}
