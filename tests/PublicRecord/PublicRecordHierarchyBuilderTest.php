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
}
