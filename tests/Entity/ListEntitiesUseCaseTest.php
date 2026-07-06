<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use DateTimeImmutable;
use Nene2\Http\UtcClock;
use NeNeRecords\Analytics\AccessLogEntry;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityListCriteria;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\Entity\ListEntitiesInput;
use NeNeRecords\Entity\ListEntitiesUseCase;
use NeNeRecords\Tests\Analytics\InMemoryAccessLogRepository;
use PHPUnit\Framework\TestCase;

final class ListEntitiesUseCaseTest extends TestCase
{
    private function hit(string $path): AccessLogEntry
    {
        return new AccessLogEntry(
            requestId: null,
            method: 'GET',
            path: $path,
            statusCode: 200,
            durationMs: 1.0,
            accessedAt: new DateTimeImmutable(),
        );
    }

    public function testPopulatesPerEntityViewCountsWhenIncludeViewsIsSet(): void
    {
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, permalink: '/a', status: EntityStatus::Published),
            new Entity(id: 2, entityTypeId: 1, permalink: '/b', status: EntityStatus::Published),
        ]);
        $accessLogs = new InMemoryAccessLogRepository();
        $accessLogs->insert($this->hit('/api/v1/entities/1'));
        $accessLogs->insert($this->hit('/api/v1/entities/1'));
        $accessLogs->insert($this->hit('/api/v1/entities/2'));
        // A nested path must NOT count as a view of entity 1.
        $accessLogs->insert($this->hit('/api/v1/entities/1/revisions'));

        $useCase = new ListEntitiesUseCase($entities, new UtcClock(), $accessLogs);

        $output = $useCase->execute(new ListEntitiesInput(includeViews: true));

        $viewCountById = [];
        foreach ($output->items as $item) {
            $viewCountById[$item->id] = $item->viewCount;
        }
        self::assertSame(2, $viewCountById[1] ?? null);
        self::assertSame(1, $viewCountById[2] ?? null);
    }

    public function testLeavesViewCountsAtZeroByDefault(): void
    {
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, permalink: '/a', status: EntityStatus::Published),
        ]);
        $accessLogs = new InMemoryAccessLogRepository();
        $accessLogs->insert($this->hit('/api/v1/entities/1'));

        $useCase = new ListEntitiesUseCase($entities, new UtcClock(), $accessLogs);

        // No includeViews → the GROUP BY is skipped and every viewCount stays 0.
        $output = $useCase->execute(new ListEntitiesInput());

        self::assertSame(0, $output->items[0]->viewCount ?? null);
    }

    public function testHasPermalinkRestrictsToRecordsWithACustomPermalink(): void
    {
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, permalink: '/a', status: EntityStatus::Published),
            new Entity(id: 2, entityTypeId: 1, permalink: null, status: EntityStatus::Published),
            new Entity(id: 3, entityTypeId: 1, permalink: '', status: EntityStatus::Published),
        ]);
        $useCase = new ListEntitiesUseCase($entities, new UtcClock());

        $output = $useCase->execute(new ListEntitiesInput(
            criteria: new EntityListCriteria(hasPermalink: true),
        ));

        // Only the record with a non-empty permalink survives — total reflects that.
        self::assertSame(1, $output->total);
        self::assertSame([1], array_map(static fn ($item) => $item->id, $output->items));
    }
}
