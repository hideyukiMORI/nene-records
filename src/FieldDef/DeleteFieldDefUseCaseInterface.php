<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

interface DeleteFieldDefUseCaseInterface
{
    /**
     * @throws FieldDefNotFoundException when the field definition does not exist.
     */
    public function execute(DeleteFieldDefInput $input): void;
}
