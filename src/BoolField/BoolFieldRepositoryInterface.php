<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

interface BoolFieldRepositoryInterface
{
    public function findById(int $id): ?BoolField;

    /**
     * Active (non-soft-deleted) rows only.
     *
     * @return list<BoolField>
     */
    public function findAll(int $limit, int $offset): array;

    /**
     * Active (non-soft-deleted) rows for the given entity only.
     *
     * @return list<BoolField>
     */
    public function findByEntityId(int $entityId, int $limit, int $offset): array;

    public function save(BoolField $boolField): int;

    public function update(BoolField $boolField): void;

    /**
     * Soft delete: sets is_deleted and deleted_at.
     *
     * @throws BoolFieldNotFoundException When the id does not refer to an active row.
     */
    public function delete(int $id): void;
}
