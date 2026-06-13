<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use DateTimeImmutable;
use NeNeRecords\Analytics\AccessLogEntry;
use NeNeRecords\Analytics\GetPopularEntitiesInput;
use NeNeRecords\Analytics\GetPopularEntitiesUseCase;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use NeNeRecords\TextField\TextField;
use PHPUnit\Framework\TestCase;

final class GetPopularEntitiesUseCaseTest extends TestCase
{
    /** @return list<AccessLogEntry> */
    private function hit(string $path, int $times): array
    {
        $now = new DateTimeImmutable();
        $entries = [];
        for ($i = 0; $i < $times; ++$i) {
            $entries[] = new AccessLogEntry(null, 'GET', $path, 200, 1.0, $now);
        }

        return $entries;
    }

    public function testRanksPublishedEntitiesByViewCountWithTitles(): void
    {
        $accessLogs = new InMemoryAccessLogRepository();
        foreach ([...$this->hit('/api/v1/entities/1', 3), ...$this->hit('/api/v1/entities/2', 5)] as $entry) {
            $accessLogs->insert($entry);
        }

        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 10, slug: 'a', status: EntityStatus::Published),
            new Entity(id: 2, entityTypeId: 10, slug: 'b', status: EntityStatus::Published),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 1, fieldKey: 'title', value: 'Alpha', id: 1),
            new TextField(entityId: 2, fieldKey: 'title', value: 'Beta', id: 2),
        ]);

        $useCase = new GetPopularEntitiesUseCase($accessLogs, $entities, $textFields);
        $output = $useCase->execute(new GetPopularEntitiesInput(days: 30, limit: 5));

        self::assertCount(2, $output->items);
        self::assertSame(2, $output->items[0]->entityId);
        self::assertSame('Beta', $output->items[0]->title);
        self::assertSame(5, $output->items[0]->viewCount);
        self::assertSame(1, $output->items[1]->entityId);
        self::assertSame(3, $output->items[1]->viewCount);
    }

    public function testExcludesNonPublishedEntities(): void
    {
        $accessLogs = new InMemoryAccessLogRepository();
        foreach ([...$this->hit('/api/v1/entities/1', 9), ...$this->hit('/api/v1/entities/2', 1)] as $entry) {
            $accessLogs->insert($entry);
        }

        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 10, slug: 'draft', status: EntityStatus::Draft),
            new Entity(id: 2, entityTypeId: 10, slug: 'live', status: EntityStatus::Published),
        ]);

        $useCase = new GetPopularEntitiesUseCase($accessLogs, $entities, new InMemoryTextFieldRepository());
        $output = $useCase->execute(new GetPopularEntitiesInput(days: 30, limit: 5));

        self::assertCount(1, $output->items);
        self::assertSame(2, $output->items[0]->entityId);
        self::assertNull($output->items[0]->title);
    }

    public function testHonoursLimit(): void
    {
        $accessLogs = new InMemoryAccessLogRepository();
        foreach ([...$this->hit('/api/v1/entities/1', 3), ...$this->hit('/api/v1/entities/2', 2), ...$this->hit('/api/v1/entities/3', 1)] as $entry) {
            $accessLogs->insert($entry);
        }

        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 10, status: EntityStatus::Published),
            new Entity(id: 2, entityTypeId: 10, status: EntityStatus::Published),
            new Entity(id: 3, entityTypeId: 10, status: EntityStatus::Published),
        ]);

        $useCase = new GetPopularEntitiesUseCase($accessLogs, $entities, new InMemoryTextFieldRepository());
        $output = $useCase->execute(new GetPopularEntitiesInput(days: 30, limit: 2));

        self::assertCount(2, $output->items);
        self::assertSame([1, 2], array_map(static fn ($item) => $item->entityId, $output->items));
    }
}
