<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityArchive;

use NeNeRecords\EntityArchive\ArchivedEntity;
use NeNeRecords\EntityArchive\EntityArchiveRepositoryInterface;
use NeNeRecords\EntityType\EntityType;

final class InMemoryEntityArchiveRepository implements EntityArchiveRepositoryInterface
{
    /** @var list<ArchivedEntity> */
    private array $entries = [];

    public function archiveAndPurgeSoftDeleted(EntityType $entityType): void
    {
    }

    /** @return list<ArchivedEntity> */
    public function findByEntityTypeId(int $entityTypeId, int $limit, int $offset): array
    {
        $filtered = array_values(
            array_filter($this->entries, static fn (ArchivedEntity $e) => $e->entityTypeId === $entityTypeId),
        );

        return array_slice($filtered, $offset, $limit);
    }

    public function countByEntityTypeId(int $entityTypeId): int
    {
        return count(array_filter($this->entries, static fn (ArchivedEntity $e) => $e->entityTypeId === $entityTypeId));
    }

    public function add(ArchivedEntity $entry): void
    {
        $this->entries[] = $entry;
    }
}
