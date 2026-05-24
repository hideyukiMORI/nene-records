<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

interface IntFieldRepositoryInterface
{
    public function findById(int $id): ?IntField;

    /**
     * Active (non-soft-deleted) rows only.
     *
     * @return list<IntField>
     */
    public function findAll(int $limit, int $offset): array;

    public function save(IntField $intField): int;

    public function update(IntField $intField): void;

    /**
     * Soft delete: sets is_deleted and deleted_at.
     *
     * @throws IntFieldNotFoundException When the id does not refer to an active row.
     */
    public function delete(int $id): void;
}
