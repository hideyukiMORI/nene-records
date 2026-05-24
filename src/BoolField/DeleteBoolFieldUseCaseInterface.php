<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

interface DeleteBoolFieldUseCaseInterface
{
    /**
     * Soft-deletes the int field.
     *
     * @throws BoolFieldNotFoundException when no int field matches the given id.
     */
    public function execute(DeleteBoolFieldByIdInput $input): void;
}
