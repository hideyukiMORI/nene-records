<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityArchive;

use DateTimeImmutable;
use NeNeRecords\EntityArchive\ArchivedEntity;
use NeNeRecords\EntityArchive\GetEntityArchiveCsvInput;
use NeNeRecords\EntityArchive\GetEntityArchiveCsvUseCase;
use PHPUnit\Framework\TestCase;

final class GetEntityArchiveCsvUseCaseTest extends TestCase
{
    private function makeArchivedEntity(int $originalEntityId, int $entityTypeId): ArchivedEntity
    {
        return new ArchivedEntity(
            originalEntityId: $originalEntityId,
            entityTypeId: $entityTypeId,
            entityTypeSlug: 'type-' . $entityTypeId,
            entityTypeName: 'Type ' . $entityTypeId,
            entitySlug: null,
            entityStatus: 'published',
            deletedAt: null,
            archivedAt: new DateTimeImmutable('2024-01-01T00:00:00Z'),
            archivedReason: 'manual',
            snapshot: [],
        );
    }

    public function testReturnsEmptyRowsWhenArchiveIsEmpty(): void
    {
        $archive = new InMemoryEntityArchiveRepository();
        $useCase = new GetEntityArchiveCsvUseCase($archive);

        $output = $useCase->execute(new GetEntityArchiveCsvInput(entityTypeId: 1));

        self::assertSame(1, $output->entityTypeId);
        self::assertSame([], $output->rows);
    }

    public function testReturnsAllRowsForGivenEntityTypeId(): void
    {
        $archive = new InMemoryEntityArchiveRepository();
        $archive->add($this->makeArchivedEntity(10, 1));
        $archive->add($this->makeArchivedEntity(11, 1));
        $archive->add($this->makeArchivedEntity(12, 1));

        $useCase = new GetEntityArchiveCsvUseCase($archive);
        $output = $useCase->execute(new GetEntityArchiveCsvInput(entityTypeId: 1));

        self::assertSame(1, $output->entityTypeId);
        self::assertCount(3, $output->rows);
        self::assertSame(10, $output->rows[0]->originalEntityId);
        self::assertSame(11, $output->rows[1]->originalEntityId);
        self::assertSame(12, $output->rows[2]->originalEntityId);
    }

    public function testOnlyReturnsRowsForMatchingEntityTypeId(): void
    {
        $archive = new InMemoryEntityArchiveRepository();
        $archive->add($this->makeArchivedEntity(10, 1));
        $archive->add($this->makeArchivedEntity(20, 2));
        $archive->add($this->makeArchivedEntity(21, 2));

        $useCase = new GetEntityArchiveCsvUseCase($archive);
        $output = $useCase->execute(new GetEntityArchiveCsvInput(entityTypeId: 2));

        self::assertSame(2, $output->entityTypeId);
        self::assertCount(2, $output->rows);
        self::assertSame(20, $output->rows[0]->originalEntityId);
        self::assertSame(21, $output->rows[1]->originalEntityId);
    }
}
