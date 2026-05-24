<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

interface EnumFieldRepositoryInterface
{
    public function findById(int $id): ?EnumField;

    /**
     * Active (non-soft-deleted) rows only.
     *
     * @return list<EnumField>
     */
    public function findAll(int $limit, int $offset): array;

    /**
     * Active (non-soft-deleted) rows for the given entity only.
     *
     * @return list<EnumField>
     */
    public function findByEntityId(int $entityId, int $limit, int $offset): array;

    public function save(EnumField $intField): int;

    public function update(EnumField $intField): void;

    /**
     * Soft delete: sets is_deleted and deleted_at.
     *
     * @throws EnumFieldNotFoundException When the id does not refer to an active row.
     */
    public function delete(int $id): void;
}
