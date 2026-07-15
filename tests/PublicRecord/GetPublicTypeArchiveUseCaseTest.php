<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use DateTimeImmutable;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\PublicRecord\GetPublicTypeArchiveUseCase;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use NeNeRecords\TextField\TextField;
use PHPUnit\Framework\TestCase;

final class GetPublicTypeArchiveUseCaseTest extends TestCase
{
    /**
     * @param list<EntityType> $types
     * @param list<Entity>     $entities
     * @param list<TextField>  $textFields
     */
    private function useCase(array $types, array $entities, array $textFields = []): GetPublicTypeArchiveUseCase
    {
        $entityRepo = new InMemoryEntityRepository($entities);

        return new GetPublicTypeArchiveUseCase(
            new InMemoryEntityTypeRepository($types),
            $entityRepo,
            new InMemoryTextFieldRepository($textFields, $entityRepo),
        );
    }

    public function testUnknownTypeSlugYieldsNull(): void
    {
        $archive = $this->useCase([new EntityType(name: 'Posts', slug: 'posts', id: 2)], [])->execute('blog');

        self::assertNull($archive, 'a slug with no type must not synthesize an archive');
    }

    public function testListsOnlyPublishedRecordsOfTheType(): void
    {
        $types = [new EntityType(name: 'Posts', slug: 'posts', id: 2, permalinkPattern: '/posts/{slug}')];
        $entities = [
            new Entity(id: 1, entityTypeId: 2, slug: 'live', status: EntityStatus::Published),
            new Entity(id: 2, entityTypeId: 2, slug: 'wip', status: EntityStatus::Draft),
            // Same status, different type: must not leak into this archive.
            new Entity(id: 3, entityTypeId: 9, slug: 'other', status: EntityStatus::Published),
        ];

        $archive = $this->useCase($types, $entities)->execute('posts');

        self::assertNotNull($archive);
        self::assertSame(1, $archive->total);
        self::assertCount(1, $archive->items);
        self::assertSame('/posts/live', $archive->items[0]->path);
    }

    /** Labels follow the #875 rule: title → meta_title → stripped+capped excerpt. */
    public function testTitlelessRecordUsesMetaTitleRatherThanRawHtml(): void
    {
        $types = [new EntityType(name: 'Pages', slug: 'pages', id: 3, permalinkPattern: '/{slug}')];
        $entities = [
            new Entity(id: 7, entityTypeId: 3, slug: 'company', permalink: '/company', status: EntityStatus::Published, metaTitle: '会社案内'),
        ];
        $textFields = [
            new TextField(entityId: 7, fieldKey: 'content', value: '<div><nav>SERVICE</nav></div>', id: 1),
        ];

        $archive = $this->useCase($types, $entities, $textFields)->execute('pages');

        self::assertNotNull($archive);
        self::assertSame('会社案内', $archive->items[0]->label);
        self::assertStringNotContainsString('<div', $archive->items[0]->label);
    }

    /** A per-record custom permalink is the canonical path and wins over the type pattern. */
    public function testCustomPermalinkWinsOverTheTypePattern(): void
    {
        $types = [new EntityType(name: 'Pages', slug: 'pages', id: 3, permalinkPattern: '/pages/{slug}')];
        $entities = [
            new Entity(id: 7, entityTypeId: 3, slug: 'privacy', permalink: '/privacy', status: EntityStatus::Published),
        ];

        $archive = $this->useCase($types, $entities)->execute('pages');

        self::assertNotNull($archive);
        self::assertSame('/privacy', $archive->items[0]->path);
    }

    public function testPaginationExposesNeighbouringOffsetsAndClampsNegatives(): void
    {
        $types = [new EntityType(name: 'Posts', slug: 'posts', id: 2, permalinkPattern: '/posts/{id}')];
        $entities = [];
        for ($i = 1; $i <= 45; $i++) {
            $entities[] = new Entity(
                id: $i,
                entityTypeId: 2,
                slug: 'p' . $i,
                status: EntityStatus::Published,
                publishedAt: new DateTimeImmutable('2026-01-01 00:00:00'),
            );
        }

        $useCase = $this->useCase($types, $entities);

        $first = $useCase->execute('posts');
        self::assertNotNull($first);
        self::assertSame(45, $first->total);
        self::assertCount(GetPublicTypeArchiveUseCase::PAGE_SIZE, $first->items);
        self::assertNull($first->prevOffset(), 'first page has no previous');
        self::assertSame(20, $first->nextOffset());

        $last = $useCase->execute('posts', 40);
        self::assertNotNull($last);
        self::assertCount(5, $last->items);
        self::assertSame(20, $last->prevOffset());
        self::assertNull($last->nextOffset(), 'last page has no next');

        $clamped = $useCase->execute('posts', -10);
        self::assertNotNull($clamped);
        self::assertSame(0, $clamped->offset);
    }

    public function testEmptyTypeYieldsAnEmptyArchiveRatherThanNull(): void
    {
        $archive = $this->useCase([new EntityType(name: 'Posts', slug: 'posts', id: 2)], [])->execute('posts');

        self::assertNotNull($archive, 'a real type with no records is still a real page');
        self::assertSame(0, $archive->total);
        self::assertSame([], $archive->items);
        self::assertNull($archive->nextOffset());
    }
}
