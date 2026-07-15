<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\PublicRecord\PublicRecordHierarchyBuilder;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use NeNeRecords\TextField\TextField;
use PHPUnit\Framework\TestCase;

final class PublicRecordHierarchyBuilderTest extends TestCase
{
    private function builder(InMemoryEntityRepository $entities, InMemoryTextFieldRepository $textFields): PublicRecordHierarchyBuilder
    {
        return new PublicRecordHierarchyBuilder($entities, $textFields);
    }

    public function testFlatSlugRecordHasNoHierarchy(): void
    {
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, slug: 'hello', status: EntityStatus::Published),
        ]);
        $textFields = new InMemoryTextFieldRepository([], $entities);

        $hierarchy = $this->builder($entities, $textFields)->build(null, '/posts/hello', 'Hello');

        self::assertSame([], $hierarchy->breadcrumbs);
        self::assertSame([], $hierarchy->childPages);
    }

    public function testBreadcrumbLinksPublishedAncestorsAndLabelsPhantomSegments(): void
    {
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, slug: 'about', permalink: '/company/about', status: EntityStatus::Published),
            new Entity(id: 2, entityTypeId: 1, slug: 'team', permalink: '/company/about/team', status: EntityStatus::Published),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 1, fieldKey: 'title', value: 'About Us', id: 1),
            new TextField(entityId: 2, fieldKey: 'title', value: 'Our Team', id: 2),
        ], $entities);

        $hierarchy = $this->builder($entities, $textFields)->buildById(2);

        self::assertCount(3, $hierarchy->breadcrumbs);

        // `/company` has no page of its own → humanized label, not linked.
        self::assertSame('Company', $hierarchy->breadcrumbs[0]->label);
        self::assertNull($hierarchy->breadcrumbs[0]->path);
        self::assertFalse($hierarchy->breadcrumbs[0]->current);

        // `/company/about` is a published page → its real title + a link.
        self::assertSame('About Us', $hierarchy->breadcrumbs[1]->label);
        self::assertSame('/company/about', $hierarchy->breadcrumbs[1]->path);
        self::assertFalse($hierarchy->breadcrumbs[1]->current);

        // The current page: marked current; path kept for the JSON-LD last item.
        self::assertSame('Our Team', $hierarchy->breadcrumbs[2]->label);
        self::assertSame('/company/about/team', $hierarchy->breadcrumbs[2]->path);
        self::assertTrue($hierarchy->breadcrumbs[2]->current);
    }

    public function testChildPagesAreDirectPublishedChildrenOnly(): void
    {
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, slug: 'about', permalink: '/company/about', status: EntityStatus::Published),
            new Entity(id: 2, entityTypeId: 1, slug: 'team', permalink: '/company/about/team', status: EntityStatus::Published),
            new Entity(id: 3, entityTypeId: 1, slug: 'history', permalink: '/company/about/history', status: EntityStatus::Published),
            // grandchild — one level too deep, must be excluded
            new Entity(id: 4, entityTypeId: 1, slug: 'leads', permalink: '/company/about/team/leads', status: EntityStatus::Published),
            // direct child but unpublished — must be excluded
            new Entity(id: 5, entityTypeId: 1, slug: 'secret', permalink: '/company/about/secret', status: EntityStatus::Draft),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 2, fieldKey: 'title', value: 'Team', id: 1),
            new TextField(entityId: 3, fieldKey: 'title', value: 'History', id: 2),
        ], $entities);

        $hierarchy = $this->builder($entities, $textFields)->buildById(1);

        // Ordered by permalink ascending; grandchild and draft excluded.
        self::assertSame(
            ['/company/about/history', '/company/about/team'],
            array_map(static fn ($child) => $child->path, $hierarchy->childPages),
        );
        self::assertSame(
            ['History', 'Team'],
            array_map(static fn ($child) => $child->title, $hierarchy->childPages),
        );
    }

    public function testUnpublishedAncestorIsNotLinked(): void
    {
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, slug: 'about', permalink: '/company/about', status: EntityStatus::Draft),
            new Entity(id: 2, entityTypeId: 1, slug: 'team', permalink: '/company/about/team', status: EntityStatus::Published),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 1, fieldKey: 'title', value: 'About Us', id: 1),
            new TextField(entityId: 2, fieldKey: 'title', value: 'Our Team', id: 2),
        ], $entities);

        $hierarchy = $this->builder($entities, $textFields)->buildById(2);

        // The draft `/company/about` is not linked; falls back to a humanized label.
        self::assertSame('About', $hierarchy->breadcrumbs[1]->label);
        self::assertNull($hierarchy->breadcrumbs[1]->path);
    }

    public function testBuildByIdReturnsEmptyForUnpublishedRecord(): void
    {
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, slug: 'about', permalink: '/company/about', status: EntityStatus::Draft),
        ]);
        $textFields = new InMemoryTextFieldRepository([], $entities);

        $hierarchy = $this->builder($entities, $textFields)->buildById(1);

        self::assertSame([], $hierarchy->breadcrumbs);
        self::assertSame([], $hierarchy->childPages);
    }

    /**
     * Regression (#875): a bespoke page (bare layout, one html field, no `title`)
     * must not dump its whole source into an ancestor crumb or a child link.
     * Production served a 57KB breadcrumb label and a 61.8KB JSON-LD BreadcrumbList
     * this way — the public twin of the admin listing bug (#849/#853).
     */
    public function testTitlelessBespokeAncestorAndChildLabelsAreDerivedNotRawHtml(): void
    {
        $body = '<div style="background:#F6F3EC"><header>' . str_repeat('本文テキスト', 400) . '</header></div>';

        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, slug: 'company', permalink: '/company', status: EntityStatus::Published),
            new Entity(id: 2, entityTypeId: 1, slug: 'ceo', permalink: '/company/ceo', status: EntityStatus::Published),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 1, fieldKey: 'content', value: $body, id: 1),
            new TextField(entityId: 2, fieldKey: 'content', value: $body, id: 2),
        ], $entities);

        $hierarchy = $this->builder($entities, $textFields)->buildById(2);

        $ancestor = $hierarchy->breadcrumbs[0]->label;
        self::assertStringNotContainsString('<div', $ancestor, 'markup must be stripped');
        self::assertStringNotContainsString('style=', $ancestor);
        self::assertLessThanOrEqual(121, mb_strlen($ancestor), 'derived label must stay capped');
        self::assertStringEndsWith('…', $ancestor);

        $child = $this->builder($entities, $textFields)->build('/company', '/company', 'Company')->childPages;
        self::assertCount(1, $child);
        self::assertStringNotContainsString('<div', $child[0]->title);
        self::assertLessThanOrEqual(121, mb_strlen($child[0]->title));
    }

    /** #875: meta_title beats the derived excerpt, mirroring the admin listing (#853). */
    public function testMetaTitleBeatsDerivedExcerptForTitlelessPages(): void
    {
        $body = '<div><nav>SERVICE PRODUCTS COMPANY</nav><h1>会社案内</h1></div>';

        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, slug: 'company', permalink: '/company', status: EntityStatus::Published, metaTitle: '会社案内｜彩音インターナショナル株式会社'),
            new Entity(id: 2, entityTypeId: 1, slug: 'ceo', permalink: '/company/ceo', status: EntityStatus::Published, metaTitle: '代表紹介 森 秀之'),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 1, fieldKey: 'content', value: $body, id: 1),
            new TextField(entityId: 2, fieldKey: 'content', value: $body, id: 2),
        ], $entities);

        $hierarchy = $this->builder($entities, $textFields)->buildById(2);
        self::assertSame('会社案内｜彩音インターナショナル株式会社', $hierarchy->breadcrumbs[0]->label);

        $children = $this->builder($entities, $textFields)->build('/company', '/company', 'Company')->childPages;
        self::assertSame('代表紹介 森 秀之', $children[0]->title);
    }

    /** #875: an explicit `title` field still wins over meta_title and is kept verbatim. */
    public function testExplicitTitleFieldStillWinsOverMetaTitle(): void
    {
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, slug: 'about', permalink: '/company/about', status: EntityStatus::Published, metaTitle: 'SEO Title'),
            new Entity(id: 2, entityTypeId: 1, slug: 'team', permalink: '/company/about/team', status: EntityStatus::Published),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 1, fieldKey: 'title', value: 'About Us', id: 1),
            new TextField(entityId: 2, fieldKey: 'title', value: 'Our Team', id: 2),
        ], $entities);

        $hierarchy = $this->builder($entities, $textFields)->buildById(2);
        self::assertSame('About Us', $hierarchy->breadcrumbs[1]->label);
    }
}
