<?php

declare(strict_types=1);

namespace NeNeRecords\EntityArchive;

use NeNeRecords\EntityType\EntityType;

interface EntityArchiveRepositoryInterface
{
    /**
     * Archives all soft-deleted entities for the given entity type, then physically
     * removes their field values and entity rows so the entity type can be deleted.
     *
     * Call this before deleting the entity type itself.
     */
    public function archiveAndPurgeSoftDeleted(EntityType $entityType): void;

    /** @return list<ArchivedEntity> */
    public function findByEntityTypeId(int $entityTypeId, int $limit, int $offset): array;

    public function countByEntityTypeId(int $entityTypeId): int;
}
