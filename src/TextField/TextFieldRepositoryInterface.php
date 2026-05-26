<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

interface TextFieldRepositoryInterface
{
    public function findById(int $id): ?TextField;

    /**
     * Active (non-soft-deleted) rows only.
     * When $locale is given, only rows with that locale are returned.
     *
     * @return list<TextField>
     */
    public function findAll(int $limit, int $offset, ?string $locale = null): array;

    /**
     * Active (non-soft-deleted) rows for the given entity only.
     * When $locale is given, only rows with that locale are returned.
     *
     * @return list<TextField>
     */
    public function findByEntityId(int $entityId, int $limit, int $offset, ?string $locale = null): array;

    /**
     * Active (non-soft-deleted) rows for entities belonging to the given entity type.
     * When $locale is given, only rows with that locale are returned.
     *
     * @return list<TextField>
     */
    public function findByEntityTypeId(int $entityTypeId, int $limit, int $offset, ?string $locale = null): array;

    /**
     * Active (non-soft-deleted) rows for a batch of entity ids (no limit).
     *
     * @param list<int> $entityIds
     * @return list<TextField>
     */
    public function findByEntityIds(array $entityIds): array;

    public function save(TextField $textField): int;

    public function update(TextField $textField): void;

    /**
     * Soft delete: sets is_deleted and deleted_at.
     *
     * @throws TextFieldNotFoundException When the id does not refer to an active row.
     */
    public function delete(int $id): void;
}
