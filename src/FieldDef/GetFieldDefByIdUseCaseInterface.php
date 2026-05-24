<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

interface GetFieldDefByIdUseCaseInterface
{
    /**
     * @throws FieldDefNotFoundException when the field definition does not exist.
     */
    public function execute(GetFieldDefByIdInput $input): GetFieldDefByIdOutput;
}
