<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

interface UpdateBoolFieldUseCaseInterface
{
    /**
     * @throws BoolFieldNotFoundException when no int field matches the given id.
     */
    public function execute(UpdateBoolFieldInput $input): UpdateBoolFieldOutput;
}
