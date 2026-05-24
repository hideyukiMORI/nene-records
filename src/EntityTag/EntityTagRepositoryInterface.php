<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

interface EntityTagRepositoryInterface
{
    /** @return list<EntityTagListItem> */
    public function findTagsByEntityId(int $entityId): array;

    public function isAttached(int $entityId, int $tagId): bool;

    public function attach(int $entityId, int $tagId): void;

    public function detach(int $entityId, int $tagId): void;
}
