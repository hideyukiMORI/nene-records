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
    private function useCase(): GenerateSitemapUseCase
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

        return new GenerateSitemapUseCase($types, $entities);
    }

    public function testCountsOnlyPublishedRecords(): void
    {
        self::assertSame(2, $this->useCase()->count());
    }

    public function testPageResolvesPermalinksInGlobalOrderAndExcludesDrafts(): void
    {
        $paths = array_map(static fn ($u) => $u->path, $this->useCase()->page(0, 100));

        self::assertSame(['/posts/hello', '/pages/20'], $paths); // types in order; default uses id
    }

    public function testPageSlicesTheWindowAcrossTypes(): void
    {
        $useCase = $this->useCase();

        self::assertSame(['/posts/hello'], array_map(static fn ($u) => $u->path, $useCase->page(0, 1)));
        self::assertSame(['/pages/20'], array_map(static fn ($u) => $u->path, $useCase->page(1, 1)));
        self::assertSame([], $useCase->page(2, 1)); // past the end
    }

    public function testLastmodPrefersUpdatedThenPublished(): void
    {
        $urls = $this->useCase()->page(0, 100);

        self::assertSame('2026-02-20T09:00:00+00:00', $urls[0]->lastmod); // updatedAt wins
        self::assertSame('2026-03-01T00:00:00+00:00', $urls[1]->lastmod); // falls back to publishedAt
    }

    public function testEmptyWhenNoPublishedRecords(): void
    {
        $types = new InMemoryEntityTypeRepository([new EntityType(name: 'Posts', slug: 'posts', id: 1)]);
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, status: EntityStatus::Draft),
        ]);
        $useCase = new GenerateSitemapUseCase($types, $entities);

        self::assertSame(0, $useCase->count());
        self::assertSame([], $useCase->page(0, 100));
    }
}
