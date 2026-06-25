<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use DateTimeImmutable;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\PublicRecord\GenerateSitemapUseCase;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use PHPUnit\Framework\TestCase;

final class GenerateSitemapUseCaseTest extends TestCase
{
    public function testEnumeratesOnlyPublishedRecordsAcrossTypes(): void
    {
        $types = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Posts', slug: 'posts', id: 1, permalinkPattern: '/{type}/{slug}'),
            new EntityType(name: 'Pages', slug: 'pages', id: 2), // default /{type}/{id}
        ]);
        $entities = new InMemoryEntityRepository([
            new Entity(
                id: 10,
                entityTypeId: 1,
                slug: 'hello',
                status: EntityStatus::Published,
                publishedAt: new DateTimeImmutable('2026-01-15T00:00:00+00:00'),
                updatedAt: new DateTimeImmutable('2026-02-20T09:00:00+00:00'),
            ),
            new Entity(id: 11, entityTypeId: 1, slug: 'draft', status: EntityStatus::Draft),
            new Entity(
                id: 20,
                entityTypeId: 2,
                slug: 'about',
                status: EntityStatus::Published,
                publishedAt: new DateTimeImmutable('2026-03-01T00:00:00+00:00'),
            ),
        ]);

        $urls = (new GenerateSitemapUseCase($types, $entities))->execute();
        $paths = array_map(static fn ($u) => $u->path, $urls);

        self::assertContains('/posts/hello', $paths);
        self::assertContains('/pages/20', $paths); // default pattern uses id
        self::assertNotContains('/posts/draft', $paths); // draft excluded
        self::assertCount(2, $urls);
    }

    public function testLastmodPrefersUpdatedThenPublished(): void
    {
        $types = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Posts', slug: 'posts', id: 1, permalinkPattern: '/{type}/{id}'),
        ]);
        $entities = new InMemoryEntityRepository([
            new Entity(
                id: 1,
                entityTypeId: 1,
                status: EntityStatus::Published,
                publishedAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
                updatedAt: new DateTimeImmutable('2026-05-05T12:00:00+00:00'),
            ),
        ]);

        $urls = (new GenerateSitemapUseCase($types, $entities))->execute();

        self::assertCount(1, $urls);
        self::assertSame('2026-05-05T12:00:00+00:00', $urls[0]->lastmod);
    }

    public function testEmptyWhenNoPublishedRecords(): void
    {
        $types = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Posts', slug: 'posts', id: 1),
        ]);
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, status: EntityStatus::Draft),
        ]);

        self::assertSame([], (new GenerateSitemapUseCase($types, $entities))->execute());
    }
}
