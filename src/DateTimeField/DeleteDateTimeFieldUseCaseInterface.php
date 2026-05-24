<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

interface DeleteDateTimeFieldUseCaseInterface
{
    /**
     * Soft-deletes the int field.
     *
     * @throws DateTimeFieldNotFoundException when no int field matches the given id.
     */
    public function execute(DeleteDateTimeFieldByIdInput $input): void;
}
