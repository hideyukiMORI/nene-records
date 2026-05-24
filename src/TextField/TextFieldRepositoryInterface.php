<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

interface TextFieldRepositoryInterface
{
    public function findById(int $id): ?TextField;

    /**
     * Active (non-soft-deleted) rows only.
     *
     * @return list<TextField>
     */
    public function findAll(int $limit, int $offset): array;

    public function save(TextField $textField): int;

    public function update(TextField $textField): void;

    /**
     * Soft delete: sets is_deleted and deleted_at.
     *
     * @throws TextFieldNotFoundException When the id does not refer to an active row.
     */
    public function delete(int $id): void;
}
