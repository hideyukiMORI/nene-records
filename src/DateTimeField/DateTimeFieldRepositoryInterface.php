<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

interface DateTimeFieldRepositoryInterface
{
    public function findById(int $id): ?DateTimeField;

    /**
     * Active (non-soft-deleted) rows only.
     *
     * @return list<DateTimeField>
     */
    public function findAll(int $limit, int $offset): array;

    public function save(DateTimeField $intField): int;

    public function update(DateTimeField $intField): void;

    /**
     * Soft delete: sets is_deleted and deleted_at.
     *
     * @throws DateTimeFieldNotFoundException When the id does not refer to an active row.
     */
    public function delete(int $id): void;
}
