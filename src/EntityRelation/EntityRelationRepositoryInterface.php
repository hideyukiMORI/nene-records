<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

interface EntityRelationRepositoryInterface
{
    /** @return list<ListEntityRelationItem> */
    public function findByEntityId(int $entityId): array;

    /** @return list<ListEntityRelationItem> */
    public function findByEntityIdAndFieldKey(int $entityId, string $fieldKey): array;

    public function isAttached(int $sourceEntityId, int $targetEntityId, string $fieldKey): bool;

    public function attach(int $sourceEntityId, int $targetEntityId, string $fieldKey): void;

    public function detach(int $sourceEntityId, int $targetEntityId, string $fieldKey): void;

    public function detachAllForFieldKey(int $sourceEntityId, string $fieldKey): void;
}
