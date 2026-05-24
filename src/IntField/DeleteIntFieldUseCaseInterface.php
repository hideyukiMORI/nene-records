<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

interface DeleteIntFieldUseCaseInterface
{
    /**
     * Soft-deletes the int field.
     *
     * @throws IntFieldNotFoundException when no int field matches the given id.
     */
    public function execute(DeleteIntFieldByIdInput $input): void;
}
