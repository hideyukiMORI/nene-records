<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

interface BlocksFieldRepositoryInterface
{
    public function findById(int $id): ?BlocksField;

    /**
     * Active (non-soft-deleted) rows only.
     * When $locale is given, only rows with that locale are returned.
     *
     * @return list<BlocksField>
     */
    public function findAll(int $limit, int $offset, ?string $locale = null): array;

    /**
     * Active (non-soft-deleted) rows for the given entity only.
     * When $locale is given, only rows with that locale are returned.
     *
     * @return list<BlocksField>
     */
    public function findByEntityId(int $entityId, int $limit, int $offset, ?string $locale = null): array;

    /**
     * Active (non-soft-deleted) rows for entities belonging to the given entity type.
     * When $locale is given, only rows with that locale are returned.
     *
     * @return list<BlocksField>
     */
    public function findByEntityTypeId(int $entityTypeId, int $limit, int $offset, ?string $locale = null): array;

    /**
     * Active (non-soft-deleted) rows for a batch of entity ids (no limit).
     *
     * @param list<int> $entityIds
     * @return list<BlocksField>
     */
    public function findByEntityIds(array $entityIds): array;

    public function save(BlocksField $blocksField): int;

    public function update(BlocksField $blocksField): void;

    /**
     * Soft delete: sets is_deleted and deleted_at.
     *
     * @throws BlocksFieldNotFoundException When the id does not refer to an active row.
     */
    public function delete(int $id): void;
}
