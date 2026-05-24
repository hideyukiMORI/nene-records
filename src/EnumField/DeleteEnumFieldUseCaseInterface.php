<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

interface DeleteEnumFieldUseCaseInterface
{
    /**
     * Soft-deletes the int field.
     *
     * @throws EnumFieldNotFoundException when no int field matches the given id.
     */
    public function execute(DeleteEnumFieldByIdInput $input): void;
}
